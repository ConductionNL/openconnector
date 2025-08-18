<template>
	<div class="calendarPopup" @mousedown.stop>
		<div class="calHeader">
			<button class="navBtn" @click="prevMonth">
				«
			</button>
			<div class="title">
				{{ formatMonthYear(viewStartMonth) }}
			</div>
			<button class="navBtn" @click="nextMonthNav">
				»
			</button>
		</div>
		<div class="oneCal">
			<div class="dow">
				<span v-for="d in dows" :key="d">{{ d }}</span>
			</div>
			<div class="grid">
				<button
					v-for="(day, idx) in monthDays(viewStartMonth)"
					:key="idx"
					class="cell"
					:class="cellClass(day)"
					:disabled="isDisabled(day)"
					@mouseenter="hoverDate = day"
					@mouseleave="hoverDate = null"
					@click="select(day)">
					{{ day.getDate() }}
				</button>
			</div>
		</div>
		<div class="actions">
			<button class="secondary" @click="$emit('cancel')">
				{{ t('openconnector', 'Cancel') }}
			</button>
			<button class="primary" :disabled="!tempStart" @click="apply">
				{{ t('openconnector', 'Apply') }}
			</button>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'DateRangeCalendar',
	props: {
		start: { type: Date, default: null },
		end: { type: Date, default: null },
		maxStart: { type: Date, default: null },
		firstDayMonday: { type: Boolean, default: true },
		anchorMonth: { type: Date, default: null },
	},
	emits: ['apply', 'cancel'],
	data() {
		return {
			tempStart: this.start ? new Date(this.start) : null,
			tempEnd: this.end ? new Date(this.end) : null,
			viewStartMonth: this.startOfMonth(this.anchorMonth || this.start || new Date()),
			hoverDate: null,
		}
	},
	computed: {
		dows() {
			return this.firstDayMonday ? ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] : ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
		},
	},
	watch: {
		start(newVal) {
			this.tempStart = newVal ? new Date(newVal) : null
			if (newVal) this.viewStartMonth = this.startOfMonth(newVal)
		},
		end(newVal) {
			this.tempEnd = newVal ? new Date(newVal) : null
		},
	},
	methods: {
		t,
		startOfMonth(d) {
			const nd = new Date(d.getFullYear(), d.getMonth(), 1)
			return nd
		},
		nextMonth(d) {
			return new Date(d.getFullYear(), d.getMonth() + 1, 1)
		},
		prevMonth() {
			this.viewStartMonth = new Date(this.viewStartMonth.getFullYear(), this.viewStartMonth.getMonth() - 1, 1)
		},
		nextMonthNav() {
			this.viewStartMonth = new Date(this.viewStartMonth.getFullYear(), this.viewStartMonth.getMonth() + 1, 1)
		},
		formatMonthYear(d) {
			return d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
		},
		monthDays(monthDate) {
			const firstDay = this.startOfMonth(monthDate)
			const startDow = this.firstDayMonday ? (firstDay.getDay() || 7) : firstDay.getDay()
			const days = []
			const startOffset = (this.firstDayMonday ? (startDow - 1) : startDow)
			const start = new Date(firstDay)
			start.setDate(firstDay.getDate() - startOffset)
			for (let i = 0; i < 42; i++) {
				const d = new Date(start)
				d.setDate(start.getDate() + i)
				d.setHours(0, 0, 0, 0)
				days.push(d)
			}
			return days
		},
		isSameDay(a, b) {
			return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
		},
		isBetween(d, a, b) {
			if (!a || !b) return false
			const x = d.getTime()
			return x >= a.getTime() && x <= b.getTime()
		},
		isDisabled(day) {
			if (!this.maxStart) return false
			// Disable for picking start only; end can be any date
			if (!this.tempStart) {
				return this.maxStart && day > this.maxStart
			}
			return false
		},
		cellClass(day) {
			const classes = []
			if (day.getMonth() !== this.viewStartMonth.getMonth()) {
				classes.push('otherMonth')
			}
			if (this.isSameDay(day, this.tempStart)) classes.push('start')
			const endRef = this.tempEnd || (this.tempStart && this.hoverDate && this.hoverDate > this.tempStart ? this.hoverDate : null)
			if (this.isSameDay(day, this.tempEnd)) classes.push('end')
			if (this.tempStart && endRef && this.isBetween(day, this.tempStart <= endRef ? this.tempStart : endRef, this.tempStart <= endRef ? endRef : this.tempStart)) classes.push('inRange')
			return classes
		},
		select(day) {
			day = new Date(day)
			day.setHours(0, 0, 0, 0)
			if (!this.tempStart || (this.tempStart && this.tempEnd)) {
				// start selection
				this.tempStart = day
				this.tempEnd = null
				return
			}
			// selecting end
			if (day < this.tempStart) {
				this.tempStart = day
				this.tempEnd = null
				return
			}
			this.tempEnd = day
		},
		apply() {
			this.$emit('apply', { start: this.tempStart, end: this.tempEnd })
		},
	},
}
</script>

<style scoped>
.calendarPopup {
	position: absolute;
	z-index: 1000;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 8px;
	min-width: calc(7 * 36px + 6 * 2px + 16px);
	max-width: calc(7 * 44px + 6 * 4px + 16px);
	width: auto;
	box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.calHeader { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.title { font-weight: 600; font-size: 14px; }
.navBtn { background: transparent; border: none; cursor: pointer; padding: 2px 6px; }
.oneCal { width: 100%; }
.dow { display: grid; grid-template-columns: repeat(7, 1fr); font-size: 11px; color: var(--color-text-lighter); margin-bottom: 2px; }
.dow span { text-align: center; }
.grid { display: grid; grid-template-columns: repeat(7, minmax(36px, 1fr)); gap: 2px; }
.cell { height: 28px; border-radius: 6px; border: none; background: transparent; cursor: pointer; font-size: 12px; }
.cell.otherMonth { opacity: 0.5; }
.cell.inRange { background: var(--color-background-hover); }
.cell.start, .cell.end { background: var(--color-primary); color: #fff; }
.actions { display: flex; justify-content: flex-end; gap: 6px; margin-top: 6px; }
.actions .secondary { background: transparent; border: 1px solid var(--color-border); padding: 4px 8px; border-radius: var(--border-radius); font-size: 12px; }
.actions .primary { background: var(--color-primary); color: #fff; border: none; padding: 4px 8px; border-radius: var(--border-radius); font-size: 12px; }
</style>
