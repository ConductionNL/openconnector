/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Flows surface regression — the ADR-096 index page and the consolidated
 * flow editor, as this app renders them.
 *
 * Two defects this spec pins down, both found live on 2026-08-18:
 *
 *   - `/flows` was the one page in the app that looked like a different
 *     product: the deprecated `CnFlowIndexPage` bare table, with none of
 *     `CnIndexPage`'s chrome. It is now an ordinary index page, and the
 *     "New flow" button renders — `CnIndexPage`'s `#header-actions` slot
 *     was documented from the start and wired to nothing, so the button
 *     shipped into the void while the page looked fine.
 *
 *   - "New flow" rendered an empty-state note instead of the editor. It now
 *     renders the SAME builder as an existing flow, holding only the seeded
 *     manual-trigger start node, with the toolbar on the canvas.
 *
 * @spec openspec/specs/flow-orchestration/spec.md#REQ-017
 */
import type { Locator, Page } from '@playwright/test'

import { expect, test } from '@playwright/test'
import { expectRouteMatched, gotoAppRoute } from '../support/appRoot.ts'

/**
 * Reveal a flow action, opening the sidebar's Actions menu when it is closed.
 *
 * `CnFlowSidebar` hands `flowActions` to NcAppSidebar's `#secondary-actions`
 * slot, which renders each as an `NcActionButton` inside an `NcActions` MENU.
 * The items are not in the DOM until the menu is opened, so a direct click on
 * one waits out its whole budget and reads as a missing control.
 *
 * The trigger's accessible name has moved more than once, so several are tried
 * and the failure names every one attempted rather than reporting the last.
 *
 * @param {Page} page The page.
 * @param {Locator} item The action to reveal.
 * @return {Promise<void>} Resolves once the item is visible.
 */
async function openFlowActionsMenu(page: Page, item: Locator): Promise<void> {
	if (await item.isVisible().catch(() => false)) {
		return
	}

	const triggers = [
		page.getByRole('button', { name: 'Flow actions' }),
		page.getByRole('button', {
			name: /^(Actions|Open actions menu|More actions)$/i,
		}),
		page.locator('.app-sidebar-header__menu button').first(),
	]

	for (const trigger of triggers) {
		if ((await trigger.count()) === 0) {
			continue
		}
		await trigger
			.first()
			.click()
			.catch(() => {})
		if (await item.isVisible().catch(() => false)) {
			return
		}
	}

	throw new Error(
		'could not open the flow Actions menu: tried "Flow actions", '
			+ '"Actions"/"Open actions menu"/"More actions", and the sidebar '
			+ 'header menu button, and the item never appeared',
	)
}

const RUN_ID = `e2e-ocflow-${Date.now().toString(36)}`

// Flows persist in OpenRegister's one flow store; cleanup goes to its API.
// `OCS-APIRequest` is what lets an API call through the CSRF check that a
// browser-session request would otherwise trip (see openregister's
// flow-engine.spec.ts for the long form of this note).
const API_HEADERS = {
	'OCS-APIRequest': 'true',
	Authorization: `Basic ${Buffer.from(
		`${process.env.NC_ADMIN_USER || 'admin'}:${process.env.NC_ADMIN_PASS || 'admin'}`,
	).toString('base64')}`,
}

test.use({ extraHTTPHeaders: { ...API_HEADERS } })

test.describe('the Flows surface', () => {
	test('the list is an ordinary index page with a New flow action (ADR-096)', async ({
		page,
	}) => {
		// `gotoAppRoute`, not a literal path: on a stack without pretty URLs
		// the router's base is `/index.php/apps/integriq`, and a literal
		// `/apps/integriq/flows` mounts the SPA whose router then cannot
		// match the path — the catch-all lands it on the Dashboard, which is
		// exactly what this spec's first CI run photographed.
		await gotoAppRoute(page, '/flows')
		await expectRouteMatched(page, '/flows')

		// CnIndexPage chrome, not the deprecated bespoke table.
		await expect(page.locator('.cn-index-page')).toBeVisible({ timeout: 20000 })
		await expect(page.getByRole('button', { name: 'New flow' })).toBeVisible({
			timeout: 15000,
		})
	})

	test('a new flow is the SAME editor holding only a starting point', async ({
		page,
	}) => {
		await gotoAppRoute(page, '/flows/new')
		await expectRouteMatched(page, '/flows/new')

		// The toolbar is the editor's identity — the actions that concern the
		// graph, on the graph.
		const toolbar = page.getByRole('toolbar', { name: 'Flow editor' })
		await expect(toolbar).toBeVisible({ timeout: 20000 })
		await expect(toolbar.getByRole('button', { name: 'Save' })).toBeVisible()
		// The engine runs the STORED flow, so an unsaved one cannot run.
		await expect(toolbar.getByRole('button', { name: 'Run' })).toBeDisabled()

		// The seeded start node — never the "No steps yet" empty state that
		// made creating look like a different product from editing.
		await expect(
			page.locator('.cn-flow-detail__node', {
				hasText: 'When someone runs it',
			}),
		).toBeVisible({ timeout: 15000 })
		await expect(page.getByText('No steps yet')).toHaveCount(0)

		// The step picker offers the catalogue; an in-flight catalogue must not
		// be reported as an unreadable one (the failure text used to show on
		// every first paint of this route).
		//
		// 🔴 THE PALETTE LEFT THE SIDEBAR in nextcloud-vue 2.40.0. A live
		// instance serves sixty-five step types, and a one-per-row list that
		// long in a 300px column is a scroll rather than a chooser, so it
		// became `CnFlowStepPickerModal`, opened from the toolbar. Only the
		// `.cn-flow-sidebar__palette*` CSS stayed behind, so the old locator
		// still matched a rule and timed out looking like a render failure.
		await page.locator('[data-testid="flow-add-step"]').click()
		const picker = page.locator('[data-testid="flow-step-picker"]')
		await expect(
			picker.locator('[data-testid="flow-step-picker-item"]').first(),
		).toBeVisible({ timeout: 15000 })
		await expect(page.getByText('could not be read')).toHaveCount(0)
	})

	test('saving a new flow swaps the route to the minted id', async ({
		page,
		request,
	}) => {
		await gotoAppRoute(page, '/flows/new')
		await expectRouteMatched(page, '/flows/new')

		const toolbar = page.getByRole('toolbar', { name: 'Flow editor' })
		await expect(toolbar.getByRole('button', { name: 'Save' })).toBeEnabled({
			timeout: 20000,
		})

		// Name the flow after this run so a failed cleanup is identifiable.
		//
		// 🔴 THERE IS NO TAB STRIP ANY MORE. The palette moved to a modal off
		// the toolbar and took the Steps tab with it, and nextcloud-vue 2.40.0
		// then dropped the strip outright — "a tab strip with one tab in it is
		// chrome around nothing". The flow's own fields moved to
		// `CnFlowSettingsModal`, reached from the sidebar's Actions menu.
		//
		// This is what the job was actually red on: a 60s timeout waiting for
		// `getByRole('tab', { name: 'Flow' })`, which reads as a hung editor
		// rather than as a control that no longer exists.
		const editAction = page.locator('[data-testid="flow-action-edit"]')
		await openFlowActionsMenu(page, editAction)
		await editAction.click()

		const settings = page.locator('[data-testid="flow-settings-modal"]')
		await expect(settings).toBeVisible({ timeout: 15000 })
		// By LABEL, not by the testid's descendant: `data-testid` is a
		// fallthrough attribute on `NcTextField`, so whether it lands on the
		// wrapper or on the input itself is that component's business, and
		// `[data-testid=…] input` finds nothing if it lands on the input.
		await settings.getByLabel('Name', { exact: true }).fill(`${RUN_ID} minted`)

		await toolbar.getByRole('button', { name: 'Save' }).click()

		// `replace`, not `push`: Back must still mean "the page before the
		// editor", and a reload must not land on `new` again.
		await expect(page).not.toHaveURL(/\/flows\/new$/, { timeout: 15000 })
		const minted = page.url().match(/\/flows\/([0-9a-f-]{36})/)?.[1]
		expect(minted, `minted id in ${page.url()}`).toBeTruthy()

		// Run is the observable difference between stored and unsaved.
		await expect(toolbar.getByRole('button', { name: 'Run' })).toBeEnabled({
			timeout: 15000,
		})

		// This suite cleans up what it mints.
		const del = await request.delete(`/apps/openregister/api/flows/${minted}`)
		expect(del.status()).toBe(200)
	})
})
