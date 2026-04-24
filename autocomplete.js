document.addEventListener('DOMContentLoaded', function() {
    // Find all search forms
    const searchInputs = document.querySelectorAll('.search-form input[name="search"]');

    searchInputs.forEach(input => {
        // Prepare suggestion box
        const suggestionBox = document.createElement('div');
        suggestionBox.className = 'autocomplete-suggestions';
        // The search form is relative and input is absolute, so we position relative to input's parent visually
        suggestionBox.style.cssText = 'display:none; position:absolute; top:45px; right:30px; width:300px; background:#fff; border:1px solid #ddd; border-radius:8px; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height:400px; overflow-y:auto; text-align: left; padding:10px 0;';
        
        input.parentElement.appendChild(suggestionBox);

        let debounceTimer;

        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length < 1) {
                suggestionBox.style.display = 'none';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch('search_autocomplete.php?q=' + encodeURIComponent(val))
                .then(res => res.json())
                .then(data => {
                    suggestionBox.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const a = document.createElement('a');
                            a.href = item.url;
                            a.style.cssText = 'display:flex; align-items:center; padding:10px 15px; text-decoration:none; color:#333; border-bottom:1px solid #f0f0f0; transition: background-color 0.2s;';
                            a.addEventListener('mouseenter', () => a.style.backgroundColor = '#f9f9f9');
                            a.addEventListener('mouseleave', () => a.style.backgroundColor = 'transparent');

                            if (item.type === 'category') {
                                a.innerHTML = `
                                    <div style="flex:1;">
                                        <div style="font-size:13px; font-weight:700; color:#E50010; text-transform:uppercase;">Category: ${item.title}</div>
                                    </div>
                                `;
                            } else {
                                a.innerHTML = `
                                    <img src="${item.image}" style="width:45px; height:60px; object-fit:cover; border-radius:4px; margin-right:12px; background:#eee;">
                                    <div style="flex:1;">
                                        <div style="font-size:14px; font-weight:600; text-transform:uppercase; margin-bottom:4px;">${item.title}</div>
                                        <div style="font-size:13px; color:#666;">${item.subtitle}</div>
                                    </div>
                                `;
                            }
                            suggestionBox.appendChild(a);
                        });
                        suggestionBox.style.display = 'block';
                    } else {
                        suggestionBox.innerHTML = '<div style="padding:15px; color:#777; font-size:14px; text-align:center;">No results found</div>';
                        suggestionBox.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error("Autocomplete fetch error:", err);
                });
            }, 300);
        });

        // Hide when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.parentElement.contains(e.target)) {
                suggestionBox.style.display = 'none';
            }
        });

        // Show again if it has content on focus
        input.addEventListener('focus', function() {
            if (this.value.trim().length > 0 && suggestionBox.innerHTML !== '') {
                suggestionBox.style.display = 'block';
            }
        });
    });
});
