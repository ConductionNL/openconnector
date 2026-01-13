## Refactor a View to Use Router Param `:id` as Source of Truth

This guide explains how to refactor list/detail views so the selected item is driven by the route param `:id`, not by global store selection.

### 0) To know:
- code should be written in the options api, no code can be written in the setup script.

### 1) Routing
- Ensure a route exists like `/entities/:id` that renders the parent index view (list + detail).

### 2) List Component (navigation and highlighting)
- On list row click, navigate to the route: `$router.push('/entities/' + item.id)`.
- For active state and visual selection, compare against `$route.params.id`.
- Keep store interactions for modals or actions as needed, but do not rely on store for selection.

### 3) Parent Index View (fetch by id and pass down)
- Create local state: `selectedItem`, `loading`, `loadError`.
- Watch `$route.params.id` (immediate):
  - If missing: clear `selectedItem` and optionally clear store item.
  - If present: call the store fetch action (e.g., `store.fetchEntity(id)`), set `selectedItem` from the returned entity, handle errors and 404.
    - 404 errors are not a thrown error by default, so the catch won't handle it and a loadError wont be shown. A check needs to be made if its 4XX and then throw a error.
- Render precedence in parent:
  - If no `id`: render empty state.
  - Else if `loadError`: render a generic error screen (no actions necessary).
  - Else: render the detail component.
- Pass only the data needed to the detail component (e.g., `:item`, optionally `:loading`).
- Listen to child updates (e.g., `@item-updated`) and resync from store if mutations occur.

#### 3.5) Rendering error example
```html
<NcEmptyContent v-else-if="loadError"
	class="detailContainer"
	name="Error"
	description="Failed to load endpoint.">
	<template #icon>
		<Api />
	</template>
	<template #action>
		<div style="display: flex; gap: 0.5rem;">
			<NcButton type="secondary" @click="endpointStore.setEndpointItem(null); loadError = false; $router.push('/endpoints')">
				Terug
			</NcButton>
			<NcButton type="primary" @click="endpointStore.setEndpointItem(null); loadError = false; $router.push('/endpoints'); navigationStore.setModal('editEndpoint')">
				Endpoint toevoegen
			</NcButton>
		</div>
	</template>
</NcEmptyContent>
```

### 4) Detail Component (consume props)
- Accept `item` (object) and optionally `loading` (boolean) as props.
- Render fields from `item` instead of reading a global store selection.
- For mutations (save/delete), continue using the store actions; after a successful change, emit an update event (e.g., `this.$emit('item-updated')`).
- Guard nullable fields (`item?.arrayField?.join(', ') || '-'`).

### 5) Error and Loading States
- Parent exclusively handles error display (generic error view) and loading state gating.
- Child focuses on rendering data and triggering actions.

### 6) Minimal Touches
- Avoid unrelated refactors. Keep styles and existing actions intact where possible.

### Example APIs
- Store should expose:
  - `fetchEntity(id)` that returns `{ entity }` and sets the current item for modals/actions.
  - `saveEntity(entity)`, `deleteEntity(id)`, etc.

Following these steps keeps the URL as the single source of truth, improves deep-linking, and avoids stale selection state.


