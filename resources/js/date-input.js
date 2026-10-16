/**
 * Type-first date input: masked typing plus a decade › year › month › day calendar.
 *
 * Livewire state is always Y-m-d or null. Text that isn't a valid date is
 * committed as typed so the server rejects it with the same message shown here.
 * The typing rules mirror src/TypedDateParser.php, and
 * tests/Fixtures/typed-dates.json is run through both.
 */

const padTwoDigits = (number) => String(number).padStart(2, '0')

const toIsoDate = (year, month, day) =>
    `${String(year).padStart(4, '0')}-${padTwoDigits(month)}-${padTwoDigits(day)}`

const daysInMonth = (year, month) => new Date(year, month, 0).getDate()

const decadeOf = (year) => Math.floor(year / 10) * 10

const isIsoDate = (value) => typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)

const isoDateParts = (isoDate) => {
    const [year, month, day] = isoDate.split('-').map(Number)

    return { year, month, day }
}

export default function dateInputFormComponent({ state, config }) {
    // The component's elements, captured once in init(). $refs and $root resolve
    // relative to the element whose handler is running, and a clicked calendar
    // cell is removed from the page as soon as the grid re-renders — after that
    // they come back empty.
    let rootElement = null
    let panelElement = null
    let inputElement = null
    let shellElement = null

    return {
        state,

        config,

        boxText: '',

        committedState: null,

        clientProblem: null,

        calendarView: config.startView,

        focusIsoDate: config.todayIso,

        escapeGuard: null,

        init() {
            rootElement = this.$el
            panelElement = rootElement.querySelector('.fi-date-input-panel')
            inputElement = rootElement.querySelector('.fi-date-input-text')
            shellElement = rootElement.querySelector('.fi-date-input-shell')

            this.committedState = this.state
            this.boxText = this.displayTextForState(this.state)

            this.$watch('state', (newState) => {
                if (newState === this.committedState) {
                    return
                }

                this.committedState = newState
                this.boxText = this.displayTextForState(newState)
                this.clientProblem = null
            })

            // Registered before x-float adds its own Esc listeners (it adds them on
            // every open), so this runs first and can stop Esc reaching a Filament
            // modal — which would otherwise close and lose what was typed in it.
            this.escapeGuard = (keyEvent) => this.stopEscapeLeavingOpenPanels(keyEvent)
            window.addEventListener('keydown', this.escapeGuard, true)
        },

        destroy() {
            window.removeEventListener('keydown', this.escapeGuard, true)
        },

        /* ---------- Display format ---------- */

        get partLengths() {
            return this.config.displayFormat.partOrder.map((part) => (part === 'Y' ? 4 : 2))
        },

        get autoSeparatorPositions() {
            const separatorPositions = []
            let characterPosition = 0

            this.partLengths.slice(0, -1).forEach((partLength) => {
                characterPosition += partLength
                separatorPositions.push(characterPosition)
                characterPosition += 1
            })

            return separatorPositions
        },

        formatIsoDate(isoDate) {
            const { year, month, day } = isoDateParts(isoDate)
            const partValues = { d: padTwoDigits(day), m: padTwoDigits(month), Y: String(year).padStart(4, '0') }

            return this.config.displayFormat.partOrder
                .map((part) => partValues[part])
                .join(this.config.displayFormat.separator)
        },

        displayTextForState(stateValue) {
            if (stateValue === null || stateValue === undefined || stateValue === '') {
                return ''
            }

            return isIsoDate(stateValue) ? this.formatIsoDate(stateValue) : String(stateValue)
        },

        insertSeparators(digits) {
            const separatedParts = []
            let digitOffset = 0

            for (const partLength of this.partLengths) {
                const partDigits = digits.slice(digitOffset, digitOffset + partLength)

                if (partDigits === '') {
                    break
                }

                separatedParts.push(partDigits)
                digitOffset += partLength
            }

            return separatedParts.join(this.config.displayFormat.separator)
        },

        followsAutoSeparators(typedText) {
            const separator = this.config.displayFormat.separator

            return [...typedText].every(
                (character, characterPosition) =>
                    /\d/.test(character) ||
                    (character === separator && this.autoSeparatorPositions.includes(characterPosition)),
            )
        },

        /* ---------- Reading typed text (mirrors TypedDateParser.php) ---------- */

        parseTypedText(typedText) {
            const trimmedText = typedText.trim()

            if (trimmedText === '') {
                return { kind: 'empty' }
            }

            const isoMatch = trimmedText.match(/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/)

            if (isoMatch) {
                return this.dateFromParts(isoMatch[1], isoMatch[2], isoMatch[3])
            }

            const separatedText = /^\d{8}$/.test(trimmedText) ? this.insertSeparators(trimmedText) : trimmedText
            const typedParts = separatedText.split(/[\/\-. ]+/).filter(Boolean)

            if (typedParts.length !== 3 || typedParts.some((typedPart) => !/^\d+$/.test(typedPart))) {
                return { kind: 'problem', message: this.config.messages.format }
            }

            const partsByName = Object.fromEntries(
                this.config.displayFormat.partOrder.map((part, partIndex) => [part, typedParts[partIndex]]),
            )

            return this.dateFromParts(partsByName.Y, partsByName.m, partsByName.d)
        },

        dateFromParts(yearText, monthText, dayText) {
            const year = Number(yearText)
            const month = Number(monthText)
            const day = Number(dayText)

            if (yearText.length !== 4 || year < 1000) {
                return { kind: 'problem', message: this.config.messages.fullYear }
            }

            if (month < 1 || month > 12 || day < 1 || day > daysInMonth(year, month)) {
                return { kind: 'problem', message: this.config.messages.noSuchDate }
            }

            return { kind: 'date', isoDate: toIsoDate(year, month, day) }
        },

        problemFor(parsedDate) {
            if (parsedDate.kind === 'problem') {
                return parsedDate.message
            }

            if (parsedDate.kind !== 'date') {
                return null
            }

            if (this.config.maxDate && parsedDate.isoDate > this.config.maxDate) {
                return this.config.messages.afterMax
            }

            if (this.config.minDate && parsedDate.isoDate < this.config.minDate) {
                return this.config.messages.beforeMin
            }

            return null
        },

        /* ---------- Typing and committing ---------- */

        handleInput(inputEvent) {
            const isDeleting = inputEvent.inputType?.startsWith('delete') ?? false
            let typedText = inputEvent.target.value

            if (!isDeleting && this.followsAutoSeparators(typedText)) {
                typedText = this.insertSeparators(typedText.replace(/\D/g, '').slice(0, 8))
                inputEvent.target.value = typedText
            }

            this.boxText = typedText
            this.hideServerError()
            this.showProblemOnceComplete()
        },

        showProblemOnceComplete() {
            const trimmedText = this.boxText.trim()
            const parsedDate = this.parseTypedText(this.boxText)
            const looksComplete = trimmedText.length >= 10 || (parsedDate.kind === 'date' && /\d{4}$/.test(trimmedText))

            if (!looksComplete) {
                this.clientProblem = null

                return
            }

            this.clientProblem = this.problemFor(parsedDate)

            if (parsedDate.kind === 'date' && !this.clientProblem && this.isCalendarOpen()) {
                this.focusIsoDate = parsedDate.isoDate
                this.calendarView = 'day'
            }
        },

        commitTyping() {
            const parsedDate = this.parseTypedText(this.boxText)
            const problem = this.problemFor(parsedDate)

            if (parsedDate.kind === 'empty') {
                this.boxText = ''
                this.setState(null)
            } else if (problem) {
                this.setState(this.boxText.trim())
            } else {
                this.boxText = this.formatIsoDate(parsedDate.isoDate)
                this.setState(parsedDate.isoDate)
            }

            // One message at a time: this message replaces the one from the last save.
            if (problem) {
                this.hideServerError()
            }

            this.clientProblem = problem
        },

        setState(newState) {
            this.committedState = newState
            this.state = newState
        },

        handleBoxKeydown(keyEvent) {
            // Filament saves on Ctrl/Cmd+S while focus stays in this box, so commit
            // first or the save goes out with the old date. The key still reaches Filament.
            if (keyEvent.key.toLowerCase() === 's' && (keyEvent.ctrlKey || keyEvent.metaKey)) {
                this.commitTyping()

                return
            }

            if (keyEvent.key === 'Enter') {
                keyEvent.preventDefault()
                this.commitTyping()

                if (this.isCalendarOpen()) {
                    this.closeCalendar(false)
                }

                return
            }

            if (keyEvent.key === 'ArrowDown' && keyEvent.altKey) {
                keyEvent.preventDefault()
                this.openCalendar()

                return
            }

            if (keyEvent.key === '?') {
                keyEvent.preventDefault()

                if (this.config.hasHelpPopover) {
                    this.openHelp()
                }
            }
        },

        handlePaste(pasteEvent) {
            const pastedText = (pasteEvent.clipboardData?.getData('text') ?? '').trim()
            const parsedPaste = this.parseTypedText(pastedText)

            if (parsedPaste.kind !== 'date') {
                return
            }

            pasteEvent.preventDefault()
            this.boxText = this.formatIsoDate(parsedPaste.isoDate)
            this.hideServerError()
            this.commitTyping()
        },

        handleFocusOut(focusEvent) {
            if (rootElement.contains(focusEvent.relatedTarget)) {
                return
            }

            this.commitTyping()
        },

        hideServerError() {
            rootElement
                .closest('[data-field-wrapper]')
                ?.querySelectorAll('.fi-fo-field-wrp-error-message')
                .forEach((errorMessage) => {
                    errorMessage.hidden = true
                })
        },

        /* ---------- Help pop-up (rendered beside the label) ---------- */

        openHelp() {
            this.$dispatch('filament-date-input-open-help', { fieldId: this.config.fieldId })
        },

        closeHelp() {
            this.$dispatch('filament-date-input-close-help', { fieldId: this.config.fieldId })
        },

        isHelpOpen() {
            const helpContent = document.querySelector(`[data-date-input-help-for="${CSS.escape(this.config.fieldId)}"]`)

            return helpContent?.closest('.fi-dropdown-panel')?.style.display === 'block'
        },

        stopEscapeLeavingOpenPanels(keyEvent) {
            if (keyEvent.key !== 'Escape') {
                return
            }

            const isCalendarOpen = this.isCalendarOpen()
            const isHelpOpen = this.isHelpOpen()

            if (!isCalendarOpen && !isHelpOpen) {
                return
            }

            keyEvent.stopPropagation()

            if (isCalendarOpen) {
                this.closeCalendar(panelElement.contains(document.activeElement))
            }

            if (isHelpOpen) {
                this.closeHelp()
            }
        },

        /* ---------- Calendar ---------- */

        isCalendarOpen() {
            return panelElement?.style.display === 'block'
        },

        openCalendar() {
            const parsedDate = this.parseTypedText(this.boxText)
            const shownIsoDate = parsedDate.kind === 'date' ? parsedDate.isoDate : isIsoDate(this.state) ? this.state : null

            if (shownIsoDate) {
                this.focusIsoDate = shownIsoDate
                this.calendarView = 'day'
            } else {
                this.focusIsoDate = this.clampIsoDate(this.config.todayIso)
                this.calendarView = this.config.startView
            }

            panelElement.open(shellElement)

            // x-float shows the panel on the next animation frame. Queue the focus
            // on that same frame so it runs just after the panel appears: a timer
            // fires before it in Safari, focusing a cell that is still hidden.
            requestAnimationFrame(() => this.focusCalendarCell())
        },

        closeCalendar(shouldReturnFocus) {
            panelElement.close()

            if (shouldReturnFocus) {
                inputElement.focus()
            }
        },

        toggleCalendar() {
            if (this.isCalendarOpen()) {
                this.closeCalendar(true)

                return
            }

            this.openCalendar()
        },

        focusCalendarCell() {
            this.$nextTick(() => panelElement.querySelector('.fi-date-input-cell[tabindex="0"]')?.focus())
        },

        levelLabel(level) {
            return this.config.labels.levels[level]
        },

        levelAbove(level) {
            const levelPosition = this.config.views.indexOf(level)

            return levelPosition > 0 ? this.config.views[levelPosition - 1] : null
        },

        levelBelow(level) {
            return this.config.views[this.config.views.indexOf(level) + 1] ?? null
        },

        goUpLevel() {
            const parentLevel = this.levelAbove(this.calendarView)

            if (!parentLevel) {
                return
            }

            this.calendarView = parentLevel
            this.focusCalendarCell()
        },

        clampIsoDate(isoDate) {
            if (this.config.maxDate && isoDate > this.config.maxDate) {
                return this.config.maxDate
            }

            if (this.config.minDate && isoDate < this.config.minDate) {
                return this.config.minDate
            }

            return isoDate
        },

        isWithinRange(isoDate) {
            return this.rangeOverlaps(isoDate, isoDate)
        },

        rangeOverlaps(firstIsoDate, lastIsoDate) {
            if (this.config.minDate && lastIsoDate < this.config.minDate) {
                return false
            }

            if (this.config.maxDate && firstIsoDate > this.config.maxDate) {
                return false
            }

            return true
        },

        get weekdayHeadings() {
            const weekdayNames = this.config.labels.weekdaysShort

            return [0, 1, 2, 3, 4, 5, 6].map((dayOffset) => weekdayNames[(this.config.firstDayOfWeek + dayOffset) % 7])
        },

        get calendarPage() {
            const { year: focusYear, month: focusMonth } = isoDateParts(this.focusIsoDate)
            const selectedDate = isIsoDate(this.state) ? isoDateParts(this.state) : null
            const today = isoDateParts(this.config.todayIso)

            let calendarPage

            if (this.calendarView === 'decade') {
                calendarPage = this.decadePage(focusYear, selectedDate, today)
            } else if (this.calendarView === 'year') {
                calendarPage = this.yearPage(focusYear, selectedDate, today)
            } else if (this.calendarView === 'month') {
                calendarPage = this.monthPage(focusYear, focusMonth, selectedDate, today)
            } else {
                calendarPage = this.dayPage(focusYear, focusMonth)
            }

            const tabStopCell =
                calendarPage.cells.find((calendarCell) => calendarCell.isFocused && !calendarCell.isDisabled) ??
                calendarPage.cells.find((calendarCell) => !calendarCell.isDisabled)

            calendarPage.cells.forEach((calendarCell) => {
                calendarCell.hasTabStop = calendarCell === tabStopCell
            })

            return calendarPage
        },

        decadePage(focusYear, selectedDate, today) {
            const decadesPerPage = this.config.decadesPerPage
            const anchorDecade = decadeOf(Number((this.config.maxDate ?? this.config.todayIso).slice(0, 4)))
            const pageOffset = Math.ceil(
                (anchorDecade - decadeOf(focusYear) - (decadesPerPage - 1) * 10) / (decadesPerPage * 10),
            )
            const firstDecade = anchorDecade - (decadesPerPage - 1) * 10 - pageOffset * decadesPerPage * 10
            const cells = []

            for (let decadeIndex = 0; decadeIndex < decadesPerPage; decadeIndex++) {
                const decade = firstDecade + decadeIndex * 10

                cells.push({
                    key: `decade-${decade}`,
                    label: `${decade}s`,
                    value: decade,
                    isOutside: false,
                    isDisabled: !this.rangeOverlaps(toIsoDate(decade, 1, 1), toIsoDate(decade + 9, 12, 31)),
                    isSelected: false,
                    holdsSelection: selectedDate !== null && decadeOf(selectedDate.year) === decade,
                    isToday: decadeOf(today.year) === decade,
                    isFocused: decadeOf(focusYear) === decade,
                })
            }

            return { title: `${firstDecade}s – ${firstDecade + (decadesPerPage - 1) * 10}s`, cells }
        },

        yearPage(focusYear, selectedDate, today) {
            const firstYear = decadeOf(focusYear)
            const cells = []

            for (let year = firstYear - 1; year <= firstYear + 10; year++) {
                cells.push({
                    key: `year-${year}`,
                    label: String(year),
                    value: year,
                    isOutside: year < firstYear || year > firstYear + 9,
                    isDisabled: !this.rangeOverlaps(toIsoDate(year, 1, 1), toIsoDate(year, 12, 31)),
                    isSelected: false,
                    holdsSelection: selectedDate !== null && selectedDate.year === year,
                    isToday: today.year === year,
                    isFocused: focusYear === year,
                })
            }

            return { title: `${firstYear} – ${firstYear + 9}`, cells }
        },

        monthPage(focusYear, focusMonth, selectedDate, today) {
            const cells = this.config.labels.monthsShort.map((monthName, monthIndex) => {
                const month = monthIndex + 1

                return {
                    key: `month-${month}`,
                    label: monthName,
                    value: month,
                    isOutside: false,
                    isDisabled: !this.rangeOverlaps(
                        toIsoDate(focusYear, month, 1),
                        toIsoDate(focusYear, month, daysInMonth(focusYear, month)),
                    ),
                    isSelected: false,
                    holdsSelection: selectedDate !== null && selectedDate.year === focusYear && selectedDate.month === month,
                    isToday: today.year === focusYear && today.month === month,
                    isFocused: focusMonth === month,
                }
            })

            return { title: String(focusYear), cells }
        },

        dayPage(focusYear, focusMonth) {
            const firstOfMonthWeekday = new Date(focusYear, focusMonth - 1, 1).getDay()
            const leadingDayCount = (firstOfMonthWeekday - this.config.firstDayOfWeek + 7) % 7
            const cells = []

            for (let cellIndex = 0; cellIndex < 42; cellIndex++) {
                const cellDate = new Date(focusYear, focusMonth - 1, 1 - leadingDayCount + cellIndex)
                const cellIsoDate = toIsoDate(cellDate.getFullYear(), cellDate.getMonth() + 1, cellDate.getDate())

                cells.push({
                    key: `day-${cellIsoDate}`,
                    label: String(cellDate.getDate()),
                    value: cellIsoDate,
                    isOutside: cellDate.getMonth() + 1 !== focusMonth,
                    isDisabled: !this.isWithinRange(cellIsoDate),
                    isSelected: this.state === cellIsoDate,
                    holdsSelection: false,
                    isToday: cellIsoDate === this.config.todayIso,
                    isFocused: cellIsoDate === this.focusIsoDate,
                })
            }

            return { title: `${this.config.labels.monthsLong[focusMonth - 1]} ${focusYear}`, cells }
        },

        shiftFocus(stepCount, unit) {
            const focusedDate = isoDateParts(this.focusIsoDate)
            let shiftedDate

            if (unit === 'day') {
                shiftedDate = new Date(focusedDate.year, focusedDate.month - 1, focusedDate.day + stepCount)
            }

            if (unit === 'month') {
                const targetMonthStart = new Date(focusedDate.year, focusedDate.month - 1 + stepCount, 1)
                const targetYear = targetMonthStart.getFullYear()
                const targetMonth = targetMonthStart.getMonth() + 1

                shiftedDate = new Date(targetYear, targetMonth - 1, Math.min(focusedDate.day, daysInMonth(targetYear, targetMonth)))
            }

            if (unit === 'year') {
                const targetYear = focusedDate.year + stepCount

                shiftedDate = new Date(targetYear, focusedDate.month - 1, Math.min(focusedDate.day, daysInMonth(targetYear, focusedDate.month)))
            }

            this.focusIsoDate = toIsoDate(shiftedDate.getFullYear(), shiftedDate.getMonth() + 1, shiftedDate.getDate())
        },

        pageBy(direction) {
            if (this.calendarView === 'decade') {
                this.shiftFocus(direction * this.config.decadesPerPage * 10, 'year')
            }

            if (this.calendarView === 'year') {
                this.shiftFocus(direction * 10, 'year')
            }

            if (this.calendarView === 'month') {
                this.shiftFocus(direction, 'year')
            }

            if (this.calendarView === 'day') {
                this.shiftFocus(direction, 'month')
            }
        },

        drillInto(cellValue) {
            if (this.calendarView === 'day') {
                this.chooseDate(cellValue)

                return
            }

            const focusedDate = isoDateParts(this.focusIsoDate)

            if (this.calendarView === 'decade') {
                const year = Number(cellValue) + (focusedDate.year % 10)

                this.focusIsoDate = toIsoDate(year, focusedDate.month, Math.min(focusedDate.day, daysInMonth(year, focusedDate.month)))
            }

            if (this.calendarView === 'year') {
                const year = Number(cellValue)

                this.focusIsoDate = toIsoDate(year, focusedDate.month, Math.min(focusedDate.day, daysInMonth(year, focusedDate.month)))
            }

            if (this.calendarView === 'month') {
                const month = Number(cellValue)

                this.focusIsoDate = toIsoDate(focusedDate.year, month, Math.min(focusedDate.day, daysInMonth(focusedDate.year, month)))
            }

            const nextLevel = this.levelBelow(this.calendarView)

            if (nextLevel) {
                this.calendarView = nextLevel
            }

            this.focusCalendarCell()
        },

        chooseDate(isoDate) {
            this.boxText = this.formatIsoDate(isoDate)
            this.setState(isoDate)
            this.clientProblem = null
            this.hideServerError()
            this.closeCalendar(true)
        },

        clearDate() {
            this.boxText = ''
            this.setState(null)
            this.clientProblem = null
            this.hideServerError()
            this.closeCalendar(true)
        },

        handlePanelKeydown(keyEvent) {
            const columnCount = this.calendarView === 'day' ? 7 : 3
            const arrowSteps = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -columnCount, ArrowDown: columnCount }

            if (keyEvent.key === 'PageUp' || keyEvent.key === 'PageDown') {
                keyEvent.preventDefault()
                this.pageBy(keyEvent.key === 'PageDown' ? 1 : -1)
                this.focusCalendarCell()

                return
            }

            if (keyEvent.key === 'Backspace') {
                keyEvent.preventDefault()
                this.goUpLevel()

                return
            }

            if (!(keyEvent.key in arrowSteps) || !keyEvent.target.classList.contains('fi-date-input-cell')) {
                return
            }

            keyEvent.preventDefault()

            const step = arrowSteps[keyEvent.key]

            if (this.calendarView === 'day') {
                this.shiftFocus(step, 'day')
            }

            if (this.calendarView === 'month') {
                this.shiftFocus(step, 'month')
            }

            if (this.calendarView === 'year') {
                this.shiftFocus(step, 'year')
            }

            if (this.calendarView === 'decade') {
                this.shiftFocus(step * 10, 'year')
            }

            this.focusCalendarCell()
        },
    }
}
