import {Controller} from "@hotwired/stimulus";

interface TableRow {
    id: number,
    fields: string[]
    present: boolean
}

export default class extends Controller {
    static targets = [
        "row",
        "searchView",
        "resultList",
        "searchBar",
        "presenceCell",
        "presentTableCell",
        "notPresentTableCell",
    ];
    rowTargets: HTMLTableRowElement[] | undefined;
    searchViewTarget: HTMLDivElement | undefined;
    resultListTarget: HTMLDivElement | undefined
    searchBarTarget: HTMLInputElement | undefined;
    presenceCellTargets: HTMLTableCellElement[] | undefined
    presentTableCellTarget: HTMLTableCellElement | undefined
    notPresentTableCellTarget: HTMLTableCellElement | undefined


    static values = {
        markPresentUrl: String
    }
    declare markPresentUrlValue: string

    private rows: TableRow[]|undefined;
    connect() {
        if (!this.rowTargets) {
            return
        }
        this.rows = Array.from(this.rowTargets.map(row => ({
            id: Number(row.getAttribute('data-search-id')),
            fields: JSON.parse(row.getAttribute('data-search-fields') ?? ''),
            present: Boolean(row.getAttribute('data-present'))
        } as TableRow)))
        this.resetSearchBar()
    }

    /**
     * Event callbacks
     */

    enterAttendanceMode() {
        this.searchViewTarget?.classList.remove('hidden')
        // reset everything and focus the search bar
        this.resetSearchBar()
        this.resetResultList()
    }

    leaveAttendanceMode() {
        this.searchViewTarget?.classList.add('hidden')
    }

    updateSearchResults() {
        const query = this.searchBarTarget?.value

        if (!query || query.length === 0) {
            return this.resetResultList()
        }

        const results: (TableRow & { score: number })[] = []
        this.rows?.forEach((row: TableRow) => {
            let matchScore = 0
            let containsAny = false;
            row.fields.forEach((field: string) => {
                const score = field.toLowerCase().indexOf(query.toLowerCase())
                matchScore += score < 0 ? 10000: score
                if (-1 !== score) {
                    containsAny = true
                }
            })

            if (!containsAny || row.present) {
                return
            }

            results.push({
                ...row,
                score: matchScore
            })
        })
        results.sort((a,b) => a.score - b.score);
        const displayRows = results
            .map((row) => { return {
                id: row.id,
                fields: row.fields,
                present: row.present,
            } as TableRow})

        if (!this.resultListTarget) {
            return
        }

        this.resetResultList()
        if (0 === results.length) {
            return this.resultListTarget.appendChild(this.getResultListMessage("No results found"))
        }
        results.forEach((row) => {
            this.resultListTarget?.appendChild(this.getResultElement(row))
        })

        const footer = document.createElement('div')
        footer.classList.add('footer')
        this.resultListTarget?.appendChild(footer)
    }

    async markAsPresent(event: Event) {
        const button = event.currentTarget as HTMLButtonElement
        const id = Number(button.getAttribute('data-' + this.identifier + '-id'))

        await fetch(this.markPresentUrlValue.replace('-1', id.toString()), {method: 'POST'})
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Status: ${response.status}`)
                }
                return response.json()
            })
            .then((data: number[]) => {
                this.rows = this.rows?.filter((row) => !data.includes(row.id))
                this.updateSearchResults()
                this.updateDomTable()
            })
            .catch((error) => {
                console.error('Error making api call: ', error)
            });

        if (!this.searchBarTarget) {
            return
        }
        this.resetSearchBar()
        this.resetResultList()
    }


    /**
     * Dom modifier
     */

    private resetResultList() {
        this.resultListTarget?.replaceChildren()
    }

    private resetSearchBar() {
        if (!this.searchBarTarget) {
            return
        }
        this.searchBarTarget.value = ""
        this.searchBarTarget.focus()
    }

    private updateDomTable() {
        if (!this.presenceCellTargets || !this.rows) {
            return
        }

        const unfinishedRows: TableRow[] = this.rows.filter((row) => !row.present)
        const unfinishedIds: number[] = unfinishedRows.map((row) => row.id)

        this.presenceCellTargets.forEach((cell) => {
            const id = Number(cell.getAttribute('data-row-id'))
            if (!unfinishedIds.includes(id)) {
                cell.replaceChildren(this.getPresentTableCellValue())
            } else {
                cell.replaceChildren(this.getNotPresentTableCellValue())
            }
        })
    }


    /**
     * HTML elements
     */

    private getResultListMessage(message: string): HTMLDivElement {
        const div = document.createElement('div')
        div.innerHTML = message
        div.classList.add("result-list-message")
        return div
    }

    private getResultElement(row: TableRow) {
        const element = document.createElement('div')
        const textContainer = document.createElement('div')
        const nameSpan = document.createElement('span')
        const emailSpan = document.createElement('span')
        const button = document.createElement('div')
        const checkMark = document.createElement('i')

        textContainer.appendChild(nameSpan)
        textContainer.appendChild(emailSpan)
        element.appendChild(textContainer)
        element.appendChild(button)
        button.appendChild(checkMark)

        element.classList.add('result-element')
        textContainer.classList.add('text-container')
        nameSpan.classList.add('name')
        emailSpan.classList.add('email')
        button.classList.add('mark-as-present')

        checkMark.classList.add('fa-solid')
        checkMark.classList.add('fa-check')

        nameSpan.innerHTML = row.fields[0]
        emailSpan.innerHTML = row.fields[1]

        button.setAttribute("data-action", "click->" + this.identifier + "#markAsPresent")
        button.setAttribute('data-' + this.identifier + '-id', row.id.toString())

        return element
    }

    private getPresentTableCellValue() {
        // instead of just creating a new one we get it from the dom to make sure the translation is correct
        const div = this.presentTableCellTarget!.cloneNode(true) as HTMLTableCellElement
        div.removeAttribute('data-'+this.identifier+'-target')
        div.classList.remove('hidden')
        return div as Node
    }

    private getNotPresentTableCellValue() {
        // instead of just creating a new one we get it from the dom to make sure the translation is correct
        const div = this.notPresentTableCellTarget!.cloneNode(true) as HTMLTableCellElement
        div.removeAttribute('data-'+this.identifier+'-target')
        div.classList.remove('hidden')
        return div as Node
    }

}
