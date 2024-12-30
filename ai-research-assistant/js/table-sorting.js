document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.wp-list-table');
    
    tables.forEach(table => {
        console.log('Found table:', table); // Debug
        const headers = table.querySelectorAll('th[data-sortable="true"]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            
            // Add sort direction indicator
            const sortIndicator = document.createElement('span');
            sortIndicator.classList.add('sort-indicator');
            header.appendChild(sortIndicator);
            
            header.addEventListener('click', () => {
                const column = header.cellIndex;
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                // Toggle sort direction
                const isAsc = header.getAttribute('data-sort') !== 'asc';
                
                // Reset all headers
                headers.forEach(h => {
                    h.setAttribute('data-sort', '');
                    h.querySelector('.sort-indicator').textContent = '';
                });
                
                // Set current header
                header.setAttribute('data-sort', isAsc ? 'asc' : 'desc');
                header.querySelector('.sort-indicator').textContent = isAsc ? ' ↑' : ' ↓';
                
                // Sort rows
                rows.sort((a, b) => {
                    const aVal = a.children[column].textContent.trim();
                    const bVal = b.children[column].textContent.trim();
                    
                    // Check if values are dates
                    const aDate = new Date(aVal);
                    const bDate = new Date(bVal);
                    
                    if (!isNaN(aDate) && !isNaN(bDate)) {
                        return isAsc ? aDate - bDate : bDate - aDate;
                    }
                    
                    // Check if values are numbers
                    if (!isNaN(aVal) && !isNaN(bVal)) {
                        return isAsc ? aVal - bVal : bVal - aVal;
                    }
                    
                    // Default to string comparison
                    return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                });
                
                // Reattach sorted rows
                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
});