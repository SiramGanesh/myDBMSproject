function showForm(formId){
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
}

// ----- Table row edit handlers -----
function escapeHtml(str){
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

document.addEventListener('click', function(e){
    var edit = e.target.closest('.edit-btn');
    if (edit) return handleEdit(edit);
    var save = e.target.closest('.save-btn');
    if (save) return handleSave(save);
    var cancel = e.target.closest('.cancel-btn');
    if (cancel) return handleCancel(cancel);
});

function handleEdit(btn){
    var row = btn.closest('tr');
    if (!row) return;

    if (row.dataset.editing === '1') return;
    var tds = row.querySelectorAll('td');
    var brand = tds[0].textContent.trim();
    var model = tds[1].textContent.trim();
    var price = tds[2].textContent.replace(/₹/g, '').trim();
    var quantity = tds[3].textContent.trim();

    // store original values so Cancel can restore
    row.dataset.orig = JSON.stringify({brand: brand, model: model, price: tds[2].textContent.trim(), quantity: quantity});
    row.dataset.editing = '1';

    tds[0].innerHTML = '<input type="text" value="'+escapeHtml(brand)+'">';
    tds[1].innerHTML = '<input type="text" value="'+escapeHtml(model)+'">';
    tds[2].innerHTML = '<input type="number" step="0.01" value="'+escapeHtml(price)+'">';
    tds[3].innerHTML = '<input type="number" value="'+escapeHtml(quantity)+'">';

    btn.textContent = 'Save';
    btn.classList.remove('edit-btn');
    btn.classList.add('save-btn');

    var cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'action-btn cancel-btn';
    cancel.textContent = 'Cancel';
    btn.after(cancel);
}

function handleSave(btn){
    var row = btn.closest('tr');
    if (!row) return;
    var id = row.dataset.id;
    var inputs = row.querySelectorAll('td input');
    var brand = inputs[0].value.trim();
    var model = inputs[1].value.trim();
    var price = inputs[2].value.trim();
    var quantity = inputs[3].value.trim();

    // create and submit a POST form to trigger server-side update and reload
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.pathname;

    var fields = {
        update_mobile: '1',
        id: id,
        brand: brand,
        model: model,
        price: price,
        quantity: quantity
    };

    for (var k in fields){
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = k;
        inp.value = fields[k];
        form.appendChild(inp);
    }

    document.body.appendChild(form);
    form.submit();
}

function handleCancel(btn){
    var row = btn.closest('tr');
    if (!row) return;
    var orig = row.dataset.orig ? JSON.parse(row.dataset.orig) : null;
    if (orig){
        row.querySelectorAll('td')[0].textContent = orig.brand;
        row.querySelectorAll('td')[1].textContent = orig.model;
        row.querySelectorAll('td')[2].textContent = orig.price;
        row.querySelectorAll('td')[3].textContent = orig.quantity;
    }

    row.dataset.editing = '';
    row.dataset.orig = '';

    var saveBtn = row.querySelector('.save-btn');
    if (saveBtn){
        saveBtn.textContent = 'Edit';
        saveBtn.classList.remove('save-btn');
        saveBtn.classList.add('edit-btn');
    }

    btn.remove();
}

// Remove status query params after showing message so refresh won't repeat it
function removeStatusParams(){
    try {
        var url = new URL(window.location.href);
        var params = ['updated','success','error','update_error'];
        var removed = false;
        params.forEach(function(p){
            if (url.searchParams.has(p)) {
                url.searchParams.delete(p);
                removed = true;
            }
        });
        if (removed) {
            var newUrl = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    } catch(e) {
        // ignore older browsers
    }
};

    // Run removal once page is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeStatusParams);
    } else {
        removeStatusParams();
    }

// Profile dropdown toggle and outside-click handling
(function(){
    var profileMenu = document.getElementById('profileMenu');
    if (!profileMenu) return;
    var toggle = profileMenu.querySelector('.profile-toggle');
    var dropdown = profileMenu.querySelector('.profile-dropdown');

    function openMenu(){
        profileMenu.classList.add('open');
        toggle.setAttribute('aria-expanded','true');
        dropdown.setAttribute('aria-hidden','false');
    }
    function closeMenu(){
        profileMenu.classList.remove('open');
        toggle.setAttribute('aria-expanded','false');
        dropdown.setAttribute('aria-hidden','true');
    }

    toggle.addEventListener('click', function(e){
        e.stopPropagation();
        if (profileMenu.classList.contains('open')) closeMenu(); else openMenu();
    });

    // Close when clicking outside
    document.addEventListener('click', function(e){
        if (!profileMenu.contains(e.target)) closeMenu();
    });

    // Close on Escape
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') closeMenu();
    });
})();