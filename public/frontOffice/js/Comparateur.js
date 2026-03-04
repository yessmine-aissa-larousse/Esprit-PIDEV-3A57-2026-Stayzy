

// ============================================================================
// TOGGLE COMPARATEUR (Ajouter / Retirer)
// ============================================================================
async function toggleComparateur(event, logementId) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    const icon = button.querySelector('i');
    const isActive = button.classList.contains('active');
    
    try {
        const url = isActive 
            ? `/comparateur/remove/${logementId}` 
            : `/comparateur/add/${logementId}`;
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (isActive) {
                // Retirer
                button.classList.remove('active');
                icon.classList.remove('bi-check-square-fill');
                icon.classList.add('bi-plus-square');
                showToast('✅ Retiré du comparateur', 'info');
            } else {
                // Ajouter
                button.classList.add('active');
                icon.classList.remove('bi-plus-square');
                icon.classList.add('bi-check-square-fill');
                showToast('✅ Ajouté au comparateur', 'success');
            }
            
            // Mettre à jour le compteur
            updateComparateurCount();
            
        } else {
            showToast('❌ ' + data.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        showToast('❌ Une erreur est survenue', 'error');
    }
}

// ============================================================================
// METTRE À JOUR LE COMPTEUR
// ============================================================================
async function updateComparateurCount() {
    try {
        const response = await fetch('/comparateur/count');
        const data = await response.json();
        
        const badge = document.querySelector('.comparateur-count-badge');
        const btnComparer = document.querySelector('.btn-comparer');
        
        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'inline' : 'none';
        }
        
        if (btnComparer) {
            if (data.count >= 2) {
                btnComparer.classList.remove('disabled');
                btnComparer.setAttribute('href', '/comparateur');
            } else {
                btnComparer.classList.add('disabled');
                btnComparer.setAttribute('href', '#');
            }
        }
        
    } catch (error) {
        console.error('Erreur compteur comparateur:', error);
    }
}

// ============================================================================
// VIDER LE COMPARATEUR
// ============================================================================
async function clearComparateur() {
    if (!confirm('Vider le comparateur ?')) {
        return;
    }
    
    try {
        const response = await fetch('/comparateur/clear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ Comparateur vidé', 'success');
            
            // Retirer tous les boutons actifs
            document.querySelectorAll('.compare-btn.active').forEach(btn => {
                btn.classList.remove('active');
                const icon = btn.querySelector('i');
                icon.classList.remove('bi-check-square-fill');
                icon.classList.add('bi-plus-square');
            });
            
            updateComparateurCount();
            
            // Si on est sur la page comparateur, recharger
            if (window.location.pathname === '/comparateur') {
                window.location.href = '/properties';
            }
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        showToast('❌ Une erreur est survenue', 'error');
    }
}

// ============================================================================
// INITIALISATION AU CHARGEMENT
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Attacher les événements aux boutons
    document.querySelectorAll('.compare-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            const logementId = this.dataset.logementId;
            if (logementId) {
                toggleComparateur(event, logementId);
            }
        });
    });
    
    // Mettre à jour le compteur
    updateComparateurCount();
});

// Toast (réutiliser celui des favoris si existe, sinon créer)
function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(toastContainer);
    }
    
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      'alert-info';
    
    const toast = document.createElement('div');
    toast.className = `alert ${alertClass} alert-dismissible fade show`;
    toast.style.cssText = `
        min-width: 300px;
        margin-bottom: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Exposer globalement
window.toggleComparateur = toggleComparateur;
window.clearComparateur = clearComparateur;
window.updateComparateurCount = updateComparateurCount;