@once
    @push('styles')
        <style>
            .smart-employee-select { position: relative; }
            .smart-employee-results {
                position: absolute;
                z-index: 1050;
                top: calc(100% + .35rem);
                left: 0;
                right: 0;
                max-height: 18rem;
                overflow-y: auto;
                border: 1px solid #d8e0ec;
                border-radius: .5rem;
                background: #fff;
                box-shadow: 0 .8rem 1.8rem rgba(23, 32, 51, .14);
            }
            .smart-employee-option {
                width: 100%;
                border: 0;
                background: #fff;
                padding: .75rem .9rem;
                text-align: left;
                border-bottom: 1px solid #eef2f7;
            }
            .smart-employee-option:hover,
            .smart-employee-option:focus {
                background: #f3f6fa;
                outline: none;
            }
            .smart-employee-option:last-child { border-bottom: 0; }
            .smart-employee-selected {
                border: 1px solid #d8e0ec;
                border-radius: .5rem;
                background: #f8fafc;
                padding: .75rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            window.setupSmartEmployeePicker = function (picker, options = {}) {
                if (!picker) return;

                const input = picker.querySelector('[data-smart-input]');
                const idInput = picker.querySelector('[data-smart-id]');
                const results = picker.querySelector('[data-smart-results]');
                const selected = picker.querySelector('[data-smart-selected]');
                const selectedText = picker.querySelector('[data-smart-selected-text]');
                const selectedDetails = picker.querySelector('[data-smart-selected-details]');
                const url = picker.dataset.url;
                let abortController = null;
                let searchTimer = null;

                function setPickerValidity(isValid, message = '') {
                    input?.classList.toggle('is-invalid', !isValid);
                    input?.setCustomValidity(isValid ? '' : message);
                }

                function renderSelected(employee) {
                    if (!selected || !selectedText || !selectedDetails) return;

                    if (!employee) {
                        selected.classList.add('d-none');
                        selectedText.textContent = '';
                        selectedDetails.textContent = '';
                        return;
                    }

                    selectedText.textContent = employee.text || '';
                    selectedDetails.textContent = employee.details || '';
                    selected.classList.remove('d-none');
                }

                function clearResults() {
                    results?.classList.add('d-none');
                }

                function selectEmployee(employee) {
                    input.value = employee.text || '';
                    idInput.value = employee.id || '';
                    picker.dataset.selectedEmployee = JSON.stringify(employee);
                    setPickerValidity(true);
                    renderSelected(employee);
                    clearResults();
                    options.onSelect?.(employee);
                }

                function renderResults(employees) {
                    if (!results) return;

                    results.innerHTML = '';

                    if (!employees.length) {
                        results.innerHTML = '<div class="px-3 py-2 text-muted small">No employees found.</div>';
                        results.classList.remove('d-none');
                        return;
                    }

                    employees.forEach(function (employee) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'smart-employee-option';
                        button.innerHTML = `
                            <div class="fw-semibold">${employee.text || ''}</div>
                            <div class="small text-muted">${employee.details || 'No job/location details recorded'}</div>
                            ${employee.email ? `<div class="small text-muted">${employee.email}</div>` : ''}
                        `;
                        button.addEventListener('click', function () {
                            selectEmployee(employee);
                        });
                        results.appendChild(button);
                    });

                    results.classList.remove('d-none');
                }

                try {
                    const selectedEmployee = JSON.parse(picker.dataset.selected || 'null');
                    if (selectedEmployee) {
                        picker.dataset.selectedEmployee = JSON.stringify(selectedEmployee);
                        renderSelected(selectedEmployee);
                    }
                } catch (error) {
                    picker.dataset.selectedEmployee = '';
                }

                input?.addEventListener('input', function () {
                    const query = input.value.trim();
                    idInput.value = '';
                    picker.dataset.selectedEmployee = '';
                    renderSelected(null);
                    setPickerValidity(true);
                    clearTimeout(searchTimer);

                    if (query.length < 1) {
                        clearResults();
                        return;
                    }

                    searchTimer = setTimeout(function () {
                        if (abortController) abortController.abort();

                        abortController = new AbortController();
                        results.innerHTML = '<div class="px-3 py-2 text-muted small">Searching employees...</div>';
                        results.classList.remove('d-none');

                        const params = new URLSearchParams({ q: query, limit: '10' });
                        const provinceId = options.provinceId?.();
                        if (provinceId) {
                            params.set('province_id', provinceId);
                        }

                        fetch(`${url}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' },
                            signal: abortController.signal,
                        })
                            .then(function (response) {
                                if (!response.ok) throw new Error('Employee search failed.');
                                return response.json();
                            })
                            .then(renderResults)
                            .catch(function (error) {
                                if (error.name === 'AbortError') return;
                                results.innerHTML = '<div class="px-3 py-2 text-danger small">Unable to search employees right now.</div>';
                                results.classList.remove('d-none');
                            });
                    }, 220);
                });

                input?.addEventListener('focus', function () {
                    if (input.value.trim().length >= 1 && !idInput.value) {
                        input.dispatchEvent(new Event('input'));
                    }
                });

                document.addEventListener('click', function (event) {
                    if (!picker.contains(event.target)) {
                        clearResults();
                    }
                });

                return {
                    requireSelection(message = 'Please choose an employee from the search results.') {
                        const valid = Boolean(idInput?.value);
                        setPickerValidity(valid, message);
                        return valid;
                    },
                };
            };
        </script>
    @endpush
@endonce
