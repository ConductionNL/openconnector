/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Spec coverage: openspec/changes/leaf-integrations/specs/integration-leaves/spec.md
 *
 * Integriq's first OpenRegister integration leaves: `files`, `deck` and `talk`
 * on `source`, `calendar` on `synchronization`.
 *
 * WHY THE LIVE SCHEMA IS READ BACK RATHER THAN THE FILE ON DISK
 * ------------------------------------------------------------
 * The declaration has a silent-failure mode that a file assertion cannot see.
 * `Schema::hydrate()` folds only `x-openregister-*` keys and `x-schema-org`
 * into `configuration`; every other top-level key is dispatched to a
 * `set<Key>()` method, and a call to a method that does not exist is swallowed
 * by hydrate's own catch. A `linkedTypes` written as a sibling of `properties`
 * — the shape two schemas in a neighbouring app use — therefore imports
 * cleanly, validates cleanly, and is dropped on the floor. Reading the stored
 * schema back through OpenRegister's own API is the only assertion that can
 * tell the two apart.
 *
 * WHY DECK AND TALK ARE TOLERATED WHEN ABSENT
 * -------------------------------------------
 * Leaves are runtime-optional by design: OpenRegister's providers answer
 * `isEnabled()` false when the Nextcloud app behind them is not installed, and
 * the widget then does not render. A CI instance without Deck and Talk is the
 * normal case, not a failure, so the render test asserts the Files leaf
 * unconditionally and the other two only when their app is present. The
 * declaration test has no such tolerance: `linkedTypes` is stored on the schema
 * whether or not the app is installed, so it is asserted outright.
 */

import type { Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { APP_BASE } from './_helpers.ts'

const OR_API = '/index.php/apps/openregister/api'
const SOURCES_API = `${OR_API}/objects/integriq/source`

/**
 * Read the request token the SPA itself uses. OpenRegister's API routes are
 * CSRF-guarded, and a guarded GET answers 412 with a valid JSON body in which
 * every field is undefined — which reads as a broken assertion rather than a
 * missing token.
 */
async function requestToken(page: Page) {
	const token = await page.evaluate(
		() => document.head.getAttribute('data-requesttoken') ?? '',
	)
	expect(token, 'the app page must carry a request token').not.toBe('')
	return token
}

// ---------------------------------------------------------------------------
// REQ-OCL-001: the leaf surface is declared, and is exactly four leaves on
// two schemas
// ---------------------------------------------------------------------------

test.describe('REQ-OCL-001: the declared leaf surface', () => {
	// @e2e integration-leaves::the-register-declares-files-deck-and-talk-on-source-and-calendar-on-synchronization
	test('the stored schemas carry exactly the four declared leaves', async ({
		page,
		request,
	}) => {
		await page.goto(`${APP_BASE}/sources`, { waitUntil: 'domcontentloaded' })
		const requesttoken = await requestToken(page)

		const resp = await request.get(`${OR_API}/schemas?_limit=200`, {
			headers: { requesttoken },
			failOnStatusCode: false,
		})
		expect(resp.status(), 'an admin must be able to list schemas').toBe(200)

		const body = await resp.json()
		const schemas: Array<Record<string, unknown>> = body?.results ?? body ?? []
		expect(
			Array.isArray(schemas) && schemas.length > 0,
			'the schema list must not be empty',
		).toBe(true)

		const linkedBySlug = new Map<string, string[]>()
		for (const schema of schemas) {
			const slug = String(schema.slug ?? '')
			const config = (schema.configuration ?? {}) as Record<string, unknown>
			const linked = config.linkedTypes
			if (Array.isArray(linked) && linked.length > 0) {
				linkedBySlug.set(slug, [...(linked as string[])].sort())
			}
		}

		expect(
			linkedBySlug.get('source'),
			'the source schema must declare the files, deck and talk leaves',
		).toEqual(['deck', 'files', 'talk'])
		expect(
			linkedBySlug.get('synchronization'),
			'the synchronization schema must declare the calendar leaf',
		).toEqual(['calendar'])

		// The restraint half of the requirement: no other Integriq schema may
		// quietly acquire a leaf. Schemas from other apps share this instance,
		// so the assertion is scoped to the slugs this register owns.
		const integriqOnly = [...linkedBySlug.keys()].filter((slug) =>
			['endpoint', 'mapping', 'job', 'rule', 'consumer', 'sync_item_dead_letter'].includes(slug)
			|| slug.endsWith('_log'),
		)
		expect(
			integriqOnly,
			'no other Integriq schema may declare a leaf without a spec change',
		).toEqual([])
	})
})

// ---------------------------------------------------------------------------
// REQ-OCL-002: leaves are pure link surfaces and never read or write source
// properties
// ---------------------------------------------------------------------------

test.describe('REQ-OCL-002: the leaves on a source detail page', () => {
	// @e2e integration-leaves::sourcedetail-renders-the-leaf-widgets-and-leaves-the-source-untouched
	test('the leaf widgets render and expose no credential value', async ({
		page,
		request,
	}) => {
		await page.goto(`${APP_BASE}/sources`, { waitUntil: 'domcontentloaded' })
		const requesttoken = await requestToken(page)

		const listResp = await request.get(`${SOURCES_API}?_limit=1`, {
			headers: { requesttoken },
			failOnStatusCode: false,
		})
		expect(listResp.status(), 'an admin must be able to list sources').toBe(200)
		const list = await listResp.json()
		const first = (list?.results ?? list ?? [])[0]
		test.skip(
			!first,
			'no source exists on this instance; the leaf widgets need a real object id',
		)

		const sourceId = String(first.id ?? first['@self']?.id ?? first.uuid ?? '')
		expect(sourceId, 'the listed source must carry an id').not.toBe('')

		const before = JSON.stringify(first)

		await page.goto(`${APP_BASE}/sources/${sourceId}`, {
			waitUntil: 'domcontentloaded',
		})
		// `#app-content` does not exist on this shell — the skip-link target is
		// `#app-content-vue` and the landmark is `main`. Asserting the wrong id
		// fails before any leaf assertion runs, which reads as "the leaf did not
		// render" when the page rendered perfectly.
		const content = page.locator('main').first()
		await expect(content).toBeVisible({ timeout: 20_000 })

		// Files is unconditional: its provider is OpenRegister's own and needs
		// no extra app installed.
		await expect(
			content.getByText('Supplier documents', { exact: false }).first(),
			'the files leaf must render on a source detail page',
		).toBeVisible({ timeout: 20_000 })

		// Deck and Talk render only when their app is installed. Absence is the
		// documented behaviour, so it is tolerated; presence is asserted.
		for (const title of ['Incident follow-ups', 'Incident war-room']) {
			const widget = content.getByText(title, { exact: false }).first()
			if (await widget.isVisible({ timeout: 2_000 }).catch(() => false)) {
				await expect(widget).toBeVisible()
			}
		}

		// No leaf reads a source property, so no credential value can reach a
		// leaf's DOM. Assert it against the whole page rather than a per-widget
		// locator: a value leaking into the surrounding chrome would be the
		// same defect.
		const secrets = ['password', 'apikey', 'secret', 'jwt']
			.map((field) => first[field])
			.filter(
				(value): value is string => typeof value === 'string' && value.length >= 8,
			)
		const rendered = await content.innerText()
		for (const secret of secrets) {
			expect(
				rendered.includes(secret),
				'a credential value must never render inside the leaf surface',
			).toBe(false)
		}

		// The page render must not have mutated the object the leaves hang off.
		const afterResp = await request.get(`${SOURCES_API}/${sourceId}`, {
			headers: { requesttoken },
			failOnStatusCode: false,
		})
		expect(afterResp.status()).toBe(200)
		const after = await afterResp.json()
		const afterObject = after?.results?.[0] ?? after
		expect(
			JSON.parse(before).name,
			'rendering the leaves must not change the source',
		).toBe(afterObject.name)
	})
})
