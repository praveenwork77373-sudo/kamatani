@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const institutionSelect = document.getElementById('institution-select');
    const departmentSelect = document.getElementById('department-select');
    const jobTypeSelect = document.getElementById('job-type-select');

    if (!institutionSelect || !departmentSelect) {
        return;
    }

    const departmentPlaceholder = departmentSelect.dataset.placeholder || '（全部署に適用）';
    const jobTypePlaceholder = jobTypeSelect
        ? (jobTypeSelect.dataset.placeholder || '（全職種に適用）')
        : null;
    const lookupUrlBase = @json(url('/api/institution'));

    function resetSelect(select, placeholder, keepValue) {
        if (!select) {
            return;
        }

        const valueToKeep = keepValue ? select.value : '';
        select.innerHTML = '';
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        select.appendChild(placeholderOption);

        if (keepValue && valueToKeep) {
            select.value = valueToKeep;
        }
    }

    function fillSelect(select, items, selectedId) {
        if (!select) {
            return;
        }

        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name;
            if (selectedId && String(selectedId) === String(item.id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    async function loadDepartmentsAndJobTypes(institutionId, selectedDepartmentId, selectedJobTypeId) {
        resetSelect(departmentSelect, departmentPlaceholder, false);
        if (jobTypeSelect) {
            resetSelect(jobTypeSelect, jobTypePlaceholder, false);
        }

        if (!institutionId) {
            return;
        }

        const url = `${lookupUrlBase}/${encodeURIComponent(institutionId)}/departments-job-types`;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                console.error('部署・職種の取得に失敗しました:', response.status);
                return;
            }

            const data = await response.json();
            fillSelect(departmentSelect, data.departments || [], selectedDepartmentId);
            if (jobTypeSelect) {
                fillSelect(jobTypeSelect, data.job_types || [], selectedJobTypeId);
            }
        } catch (error) {
            console.error('部署・職種の取得エラー:', error);
        }
    }

    institutionSelect.addEventListener('change', function () {
        loadDepartmentsAndJobTypes(this.value, null, null);
    });

    if (institutionSelect.value) {
        loadDepartmentsAndJobTypes(
            institutionSelect.value,
            @json(old('department_id', $selectedDepartmentId ?? null)),
            @json(old('job_type_id', $selectedJobTypeId ?? null))
        );
    }
});
</script>
@endpush
