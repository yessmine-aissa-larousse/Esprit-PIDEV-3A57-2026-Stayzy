<<<<<<< HEAD
/**
 * SYSTÈME DE FAVORIS - JavaScript avec Authentification
 */
=======
>>>>>>> origin/module-user_logement_reservation

// ============================================================================
// TOGGLE FAVORI (Ajouter / Retirer)
// ============================================================================
async function toggleFavorite(event, logementId) {
    event.preventDefault();
    event.stopPropagation();
    
    const button = event.currentTarget;
    const icon = button.querySelector('i');
    
    try {
        const response = await fetch(`/favori/toggle/${logementId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        // ⚠️ Si l'utilisateur n'est pas connecté (401)
        if (response.status === 401) {
            showToast('⚠️ Vous devez être connecté pour ajouter des favoris', 'warning');
            
            // Rediriger vers la page de connexion après 2 secondes
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
            return;
        }
        
        if (data.success) {
            if (data.action === 'added') {
                // Ajouté aux favoris
                button.classList.add('active');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                showToast('✅ Ajouté aux favoris', 'success');
                
            } else {
                // Retiré des favoris
                button.classList.remove('active');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                showToast('❌ Retiré des favoris', 'info');
                
                // Si on est sur la page favoris, supprimer la card
                if (window.location.pathname === '/mes-favoris' || 
                    window.location.pathname === '/admin/mes-favoris') {
                    const card = button.closest('.col-lg-4, .col-md-6');
                    if (card) {
                        card.remove();
                    }
                    
                    // Vérifier s'il reste des favoris
                    const remaining = document.querySelectorAll('.property-item').length;
                    if (remaining === 0) {
                        location.reload();
                    }
                }
            }
            
            // Mettre à jour le compteur
            updateFavoriCount();
            
        } else {
            showToast('❌ ' + data.message, 'error');
        }
        
    } catch (error) {
        console.error('Erreur:', error);
        showToast('❌ Une erreur est survenue', 'error');
    }
}

// ============================================================================
// INITIALISATION AU CHARGEMENT DE LA PAGE
// ============================================================================
document.addEventListener('DOMContentLoaded', async function() {
    
    // ⭐ ATTACHER LES ÉVÉNEMENTS CLICK AUX BOUTONS FAVORIS
    document.querySelectorAll('.favorite-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            const logementId = this.dataset.logementId;
            if (logementId) {
                toggleFavorite(event, logementId);
            }
        });
    });
    
    // ⭐ VÉRIFIER L'ÉTAT DES FAVORIS (pour afficher le bon icône)
    const favoriteButtons = document.querySelectorAll('.favorite-btn[data-logement-id]');
    
    for (const button of favoriteButtons) {
        const logementId = button.dataset.logementId;
        
        if (logementId) {
            try {
                const response = await fetch(`/favori/check/${logementId}`);
                const data = await response.json();
                
                if (data.isFavorite) {
                    button.classList.add('active');
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill');
                    }
                }
            } catch (error) {
                console.error('Erreur vérification favori:', error);
            }
        }
    }
    
    // Mettre à jour le compteur
    updateFavoriCount();
});

// ============================================================================
// METTRE À JOUR LE COMPTEUR DE FAVORIS
// ============================================================================
async function updateFavoriCount() {
    try {
        const response = await fetch('/favori/count');
        const data = await response.json();
        
        // Mettre à jour le badge dans le menu
        const badge = document.querySelector('.favori-count-badge');
        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'inline' : 'none';
        }
        
    } catch (error) {
        console.error('Erreur compteur favoris:', error);
    }
}

// ============================================================================
// AFFICHER UN TOAST (Notification)
// ============================================================================
function showToast(message, type = 'info') {
    // Créer le conteneur s'il n'existe pas
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
    
    // Déterminer la classe Bootstrap selon le type
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' :
                      'alert-info';
    
    // Créer le toast
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
    
    // Auto-suppression après 3 secondes
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// ============================================================================
// EXPOSER LES FONCTIONS GLOBALEMENT (pour compatibilité)
// ============================================================================
window.toggleFavorite = toggleFavorite;
window.updateFavoriCount = updateFavoriCount;