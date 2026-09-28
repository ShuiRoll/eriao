const registerGabAi = () => {
    if (!window.Alpine) {
        return
    }

    window.Alpine.data('gabAiChat', () => ({
        open: false,
        busy: false,
        waitingForFirstToken: false,
        streaming: false,
        errorMessage: '',
        draft: '',
        streamRaw: '',
        streamDisplay: '',
        messages: [
            {
                id: 1,
                role: 'assistant',
                raw: 'Hello! I am Gab AI. I can help you analyze your inventory, sales, transactions, and business performance.',
                html: '<p>Hello! I am <strong>Gab AI</strong>.</p><p>I can help you analyze your inventory, sales, transactions, and business performance.</p>'
            }
        ],
        nextId: 2,
        abortController: null,
        startUrl: '',
        csrfToken: '',
        initialized: false,

        init() {
            if (this.initialized) {
                return
            }

            this.initialized = true
            this.startUrl = this.$el.dataset.startUrl || ''
            this.csrfToken = this.$el.dataset.csrfToken || ''
            this.scrollToBottom()
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const element = this.$refs.messages

                if (element) {
                    element.scrollTop = element.scrollHeight
                }
            })
        },

        escapeHtml(value) {
            const element = document.createElement('div')
            element.textContent = value
            return element.innerHTML
        },

        sanitizeHtml(value) {
            const template = document.createElement('template')
            template.innerHTML = value

            const allowed = new Set([
                'P',
                'STRONG',
                'EM',
                'UL',
                'OL',
                'LI',
                'BR',
                'CODE',
                'PRE',
                'H3',
                'H4',
                'TABLE',
                'THEAD',
                'TBODY',
                'TR',
                'TH',
                'TD'
            ])

            template.content.querySelectorAll('*').forEach(element => {
                if (!allowed.has(element.tagName)) {
                    element.replaceWith(
                        document.createTextNode(
                            element.textContent
                        )
                    )

                    return
                }

                Array.from(
                    element.attributes
                ).forEach(attribute => {
                    element.removeAttribute(
                        attribute.name
                    )
                })
            })

            return template.innerHTML
        },

        markdownInline(value) {
            let result = this.escapeHtml(value)

            result = result.replace(
                /`([^`]+)`/g,
                '<code>$1</code>'
            )

            result = result.replace(
                /\*\*(.*?)\*\*/g,
                '<strong>$1</strong>'
            )

            result = result.replace(
                /__(.*?)__/g,
                '<strong>$1</strong>'
            )

            result = result.replace(
                /\*([^*]+)\*/g,
                '<em>$1</em>'
            )

            result = result.replace(
                /_([^_]+)_/g,
                '<em>$1</em>'
            )

            return result
        },

        markdownToHtml(value) {
            const text =
                String(value).replace(
                    /\r\n/g,
                    '\n'
                )

            const blocks =
                text.split(/\n{2,}/)

            return blocks
                .map(block => {
                    const lines =
                        block.split('\n')

                    if (
                        lines.every(
                            line =>
                                /^\s*[-*]\s+/.test(
                                    line
                                )
                        )
                    ) {
                        return (
                            '<ul>' +
                            lines
                                .map(
                                    line =>
                                        '<li>' +
                                        this.markdownInline(
                                            line.replace(
                                                /^\s*[-*]\s+/,
                                                ''
                                            )
                                        ) +
                                        '</li>'
                                )
                                .join('') +
                            '</ul>'
                        )
                    }

                    if (
                        lines.every(
                            line =>
                                /^\s*\d+\.\s+/.test(
                                    line
                                )
                        )
                    ) {
                        return (
                            '<ol>' +
                            lines
                                .map(
                                    line =>
                                        '<li>' +
                                        this.markdownInline(
                                            line.replace(
                                                /^\s*\d+\.\s+/,
                                                ''
                                            )
                                        ) +
                                        '</li>'
                                )
                                .join('') +
                            '</ol>'
                        )
                    }

                    if (
                        /^###\s+/.test(
                            lines[0]
                        )
                    ) {
                        return (
                            '<h3>' +
                            this.markdownInline(
                                lines[0].replace(
                                    /^###\s+/,
                                    ''
                                )
                            ) +
                            '</h3>'
                        )
                    }

                    if (
                        /^####\s+/.test(
                            lines[0]
                        )
                    ) {
                        return (
                            '<h4>' +
                            this.markdownInline(
                                lines[0].replace(
                                    /^####\s+/,
                                    ''
                                )
                            ) +
                            '</h4>'
                        )
                    }

                    return (
                        '<p>' +
                        lines
                            .map(
                                line =>
                                    this.markdownInline(
                                        line
                                    )
                            )
                            .join('<br>') +
                        '</p>'
                    )
                })
                .join('')
        },

        renderHtml(value) {
            if (!value) {
                return ''
            }

            const containsHtml =
                /<\/?(p|strong|em|ul|ol|li|br|code|pre|h3|h4|table|thead|tbody|tr|th|td)(\s|>)/i.test(
                    value
                )

            if (containsHtml) {
                return this.sanitizeHtml(value)
            }

            return this.markdownToHtml(value)
        },

        streamingText(value) {
            const element =
                document.createElement('div')

            element.innerHTML = value

            return (
                element.textContent ||
                element.innerText ||
                value
            )
        },

        historyPayload() {
            return this.messages
                .slice(-20)
                .map(item => ({
                    role: item.role,
                    content: item.raw
                }))
        },

        addUserMessage(value) {
            this.messages.push({
                id: this.nextId++,
                role: 'user',
                raw: value,
                html: this.escapeHtml(value)
            })

            this.scrollToBottom()
        },

        resetStream() {
            this.streamRaw = ''
            this.streamDisplay = ''
            this.waitingForFirstToken = false
            this.streaming = false
        },

        async send() {
            if (this.busy) {
                return
            }

            const message = this.draft.trim()

            if (!message) {
                return
            }

            const history =
                this.historyPayload()

            this.errorMessage = ''
            this.draft = ''

            this.addUserMessage(
                message
            )

            this.resetStream()

            this.busy = true
            this.waitingForFirstToken = true

            try {
                const startResponse =
                    await fetch(
                        this.startUrl,
                        {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type':
                                    'application/json',
                                'X-CSRF-TOKEN':
                                    this.csrfToken,
                                'X-Requested-With':
                                    'XMLHttpRequest',
                                'Cache-Control':
                                    'no-cache'
                            },
                            body:
                                JSON.stringify({
                                    message,
                                    history
                                })
                        }
                    )

                if (!startResponse.ok) {
                    throw new Error(
                        `Request failed with status ${startResponse.status}.`
                    )
                }

                const startData =
                    await startResponse.json()

                if (!startData.stream_url) {
                    throw new Error(
                        'Gab AI did not return a stream URL.'
                    )
                }

                await new Promise(
                    (resolve, reject) => {
                        let finalRaw = ''
                        let settled = false

                        const source =
                            new EventSource(
                                startData.stream_url
                            )

                        this.abortController =
                            source

                        const close = () => {
                            source.close()

                            this.abortController =
                                null
                        }

                        source.addEventListener(
                            'ready',
                            () => {
                                this.waitingForFirstToken =
                                    true

                                this.streaming =
                                    false

                                this.scrollToBottom()
                            }
                        )

                        source.addEventListener(
                            'thinking',
                            () => {
                                this.waitingForFirstToken =
                                    true

                                this.streaming =
                                    false

                                this.scrollToBottom()
                            }
                        )

                        source.addEventListener(
                            'token',
                            event => {
                                if (settled) {
                                    return
                                }

                                let data

                                try {
                                    data =
                                        JSON.parse(
                                            event.data
                                        )
                                } catch {
                                    return
                                }

                                const token =
                                    data?.content ||
                                    ''

                                if (!token) {
                                    return
                                }

                                finalRaw += token

                                this.waitingForFirstToken =
                                    false

                                this.streaming =
                                    true

                                this.streamRaw =
                                    finalRaw

                                this.streamDisplay =
                                    this.streamingText(
                                        finalRaw
                                    )

                                this.scrollToBottom()
                            }
                        )

                        source.addEventListener(
                            'stream-error',
                            event => {
                                if (settled) {
                                    return
                                }

                                let data = null

                                try {
                                    data =
                                        JSON.parse(
                                            event.data
                                        )
                                } catch {
                                    data = null
                                }

                                settled =
                                    true

                                close()

                                reject(
                                    new Error(
                                        data?.message ||
                                        'Gab AI encountered an error.'
                                    )
                                )
                            }
                        )

                        source.addEventListener(
                            'done',
                            () => {
                                if (settled) {
                                    return
                                }

                                settled =
                                    true

                                if (finalRaw) {
                                    this.messages.push({
                                        id: this.nextId++,
                                        role: 'assistant',
                                        raw: finalRaw,
                                        html:
                                            this.renderHtml(
                                                finalRaw
                                            )
                                    })
                                }

                                this.streamRaw = ''
                                this.streamDisplay = ''
                                this.waitingForFirstToken = false
                                this.streaming = false

                                close()
                                this.scrollToBottom()

                                resolve()
                            }
                        )

                        source.onerror = () => {
                            if (settled) {
                                return
                            }

                            settled = true

                            close()

                            reject(
                                new Error(
                                    'The Gab AI stream connection was interrupted.'
                                )
                            )
                        }
                    }
                )

                this.streamRaw = ''
                this.streamDisplay = ''
            } catch (error) {
                if (
                    this.abortController instanceof
                    EventSource
                ) {
                    this.abortController.close()
                    this.abortController = null
                }

                this.errorMessage =
                    error.message ||
                    'Gab AI could not complete the request.'
            } finally {
                this.busy = false
                this.waitingForFirstToken = false
                this.streaming = false
                this.abortController = null
                this.streamRaw = ''
                this.streamDisplay = ''
                this.scrollToBottom()
            }
        }
    }))
}

if (window.Alpine) {
    registerGabAi()
} else {
    document.addEventListener(
        'alpine:init',
        registerGabAi,
        {
            once: true
        }
    )
}