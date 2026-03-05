# Testing URLs - Complete Application Map

**Project:** Stayzy  
**Port:** 8000  
**Base URL:** http://localhost:8000

---

## 🔴 Critical Prerequisites

Before testing any URLs:

```bash
# 1. Start Docker containers
docker-compose up -d

# 2. Install dependencies
docker-compose exec php composer install

# 3. Run database migrations (CRITICAL)
docker-compose exec php php bin/console doctrine:migrations:migrate

# 4. Check application status
docker-compose exec php php bin/console about
docker-compose exec php php bin/console cache:clear
```

**Status Check:** Visit http://localhost:8000/ should load without errors

---

## 📍 HOME & CORE PAGES

### Home & Main Navigation
| Page | URL | Expected | Status |
|------|-----|----------|--------|
| Home Page | http://localhost:8000/ | HomePage loaded | ⏳ |
| Properties Listing | http://localhost:8000/properties | List of properties | ⏳ |
| Property Details | http://localhost:8000/property/1 | Property info (replace 1 with ID) | ⏳ |

### Portal & Authentication
| Feature | URL | Expected | Status |
|---------|-----|----------|--------|
| Login | http://localhost:8000/login | Login form | ⏳ |
| Logout | http://localhost:8000/logout | Redirect to home | ⏳ |
| Client Portal | http://localhost:8000/portal/login | Client login portal | ⏳ |
| Proprietaire Portal | http://localhost:8000/portal/register | Property owner registration | ⏳ |
| Register as Client | http://localhost:8000/register/client | Client registration form | ⏳ |
| Register as Proprietaire | http://localhost:8000/register/proprietaire | Owner registration form | ⏳ |

---

## 🏘️ LOGEMENT (PROPERTY) MANAGEMENT

### Admin Logement Management
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| List Properties | http://localhost:8000/admin/logement/list | GET | All properties in table | ⏳ |
| Add Property | http://localhost:8000/admin/logement/add | GET/POST | Property form | ⏳ |
| Edit Property | http://localhost:8000/admin/logement/edit/1 | GET/POST | Property edit form | ⏳ |
| Delete Property | http://localhost:8000/admin/logement/delete/1 | POST | Property deleted | ⏳ |
| Property Details | http://localhost:8000/admin/logement/1/details | GET | Property full details | ⏳ |
| Delete Photo | http://localhost:8000/admin/logement/photo/delete/1/photo.jpg | POST | Photo removed | ⏳ |
| Delete Main Photo | http://localhost:8000/admin/logement/photo-principale/delete/1 | POST | Main photo removed | ⏳ |

### Proprietaire Logement Management
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| My Properties | http://localhost:8000/proprietaire/logements | GET | List owner's properties | ⏳ |
| Publish Property | http://localhost:8000/proprietaire/logement/publier | GET/POST | New property form | ⏳ |
| Edit Property | http://localhost:8000/proprietaire/logement/modifier/1 | GET/POST | Edit owner's property | ⏳ |
| View Property | http://localhost:8000/proprietaire/logement/1 | GET | Property owner view | ⏳ |
| Delete Property | http://localhost:8000/proprietaire/logement/supprimer/1 | POST | Delete owned property | ⏳ |
| Filter Properties | http://localhost:8000/proprietaire/logements/filter | POST | Filter results | ⏳ |

---

## 📅 RESERVATION MANAGEMENT

### Client Reservations
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| New Reservation | http://localhost:8000/reservation/new/1 | GET/POST | Book property (ID=1) | ⏳ |
| My Reservations | http://localhost:8000/reservation/list | GET | List client bookings | ⏳ |
| Edit Reservation | http://localhost:8000/reservation/edit/1 | GET/POST | Modify booking | ⏳ |
| Cancel Reservation | http://localhost:8000/reservation/cancel/1 | POST | Cancel booking | ⏳ |

### Proprietaire Reservations
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Reservations | http://localhost:8000/reservation/proprietaire | GET | All property reservations | ⏳ |
| Approve Reservation | http://localhost:8000/reservation/proprietaire/action/1/approve | POST | Approve booking | ⏳ |
| Reject Reservation | http://localhost:8000/reservation/proprietaire/action/1/reject | POST | Reject booking | ⏳ |
| Reservation Dashboard | http://localhost:8000/reservation/proprietaire/dashboard | GET | Reservation analytics | ⏳ |

---

## 💬 FORUM SYSTEM

### Forum Posts
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Forum Home | http://localhost:8000/forum | GET | List all posts | ⏳ |
| Forum Page 2 | http://localhost:8000/forum?page=2 | GET | Posts page 2 | ⏳ |
| Create Post | http://localhost:8000/forum/post/new | GET/POST | New post form | ⏳ |
| View Post | http://localhost:8000/forum/post/1 | GET/POST | Post + comments | ⏳ |
| Edit Post | http://localhost:8000/forum/post/1/edit | GET/POST | Edit own post | ⏳ |
| Delete Post | http://localhost:8000/forum/post/1/delete | POST | Delete own post | ⏳ |
| Test Mail Alert | http://localhost:8000/forum/test-mail | GET | Test email system | ⏳ |

### Forum Admin
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Admin Posts | http://localhost:8000/admin/forum/post | GET | All posts table | ⏳ |
| Admin Posts P2 | http://localhost:8000/admin/forum/post?page=2 | GET | Posts page 2 | ⏳ |
| Post Details | http://localhost:8000/admin/forum/post/1 | GET | Post admin view | ⏳ |
| Edit Post | http://localhost:8000/admin/forum/post/1/edit | GET/POST | Admin edit post | ⏳ |
| Delete Post | http://localhost:8000/admin/forum/post/1/delete | POST | Admin delete | ⏳ |
| Comments List | http://localhost:8000/admin/forum/comment | GET | All comments | ⏳ |
| Comments Filter | http://localhost:8000/admin/forum/comment?post_id=1 | GET | Comments for post 1 | ⏳ |
| Comment View | http://localhost:8000/admin/forum/comment/1 | GET | Comment detail | ⏳ |
| Delete Comment | http://localhost:8000/admin/forum/comment/1/delete | POST | Delete comment | ⏳ |

---

## 🗣️ RECLAMATION (COMPLAINT) SYSTEM

### Client Reclamation
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Create Complaint | http://localhost:8000/reclamation/new | GET/POST | New complaint form | ⏳ |
| My Complaints | http://localhost:8000/reclamation/list | GET | List user complaints | ⏳ |
| View Complaint | http://localhost:8000/reclamation/1 | GET | Complaint details | ⏳ |
| Edit Complaint | http://localhost:8000/reclamation/edit/1 | GET/POST | Modify complaint | ⏳ |
| Delete Complaint | http://localhost:8000/reclamation/delete/1 | POST | Delete complaint | ⏳ |

### Proprietaire Reclamation Responses
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| My Responses | http://localhost:8000/reponse/liste | GET | Owner's responses | ⏳ |
| New Response | http://localhost:8000/reponse/new/1 | GET/POST | Reply to complaint | ⏳ |
| Edit Response | http://localhost:8000/reponse/edit/1 | GET/POST | Modify response | ⏳ |
| Delete Response | http://localhost:8000/reponse/delete/1 | POST | Delete response | ⏳ |

### Admin Reclamation
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Supervision | http://localhost:8000/admin/supervision-reclamation | GET | All complaints admin view | ⏳ |
| Admin Response | http://localhost:8000/reponse/admin/new/1 | GET/POST | Admin adds response | ⏳ |
| Admin Edit Response | http://localhost:8000/reponse/admin/edit/1 | GET/POST | Admin edits response | ⏳ |
| Admin Delete Response | http://localhost:8000/reponse/admin/delete/1 | POST | Admin deletes response | ⏳ |

---

## 👤 USER MANAGEMENT (Admin Panel)

### Admin User Dashboard
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Users List | http://localhost:8000/admin/user | GET | All users table | ⏳ |
| Users Pending | http://localhost:8000/admin/user/pending | GET | Pending approvals | ⏳ |
| New User | http://localhost:8000/admin/user/new | GET/POST | Create user form | ⏳ |
| User Details | http://localhost:8000/admin/user/1 | GET | User info page | ⏳ |
| Edit User | http://localhost:8000/admin/user/1/edit | GET/POST | Edit user data | ⏳ |
| Delete User | http://localhost:8000/admin/user/1/delete | POST | Remove user | ⏳ |
| Toggle Status | http://localhost:8000/admin/user/1/toggle-status | POST | Active/Inactive | ⏳ |
| Approve User | http://localhost:8000/admin/user/1/approve | POST | Approve pending | ⏳ |
| Reject User | http://localhost:8000/admin/user/1/reject | POST | Reject user | ⏳ |
| Export CSV | http://localhost:8000/admin/user/export/csv | GET | Download users | ⏳ |

---

## ⭐ FAVORITES

| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| My Favorites | http://localhost:8000/mes-favoris | GET | User favorites list | ⏳ |
| Client Favorites | http://localhost:8000/client/mes-favoris | GET | Client fav properties | ⏳ |
| Owner Favored By | http://localhost:8000/proprieyaire/favoris-clients | GET | Clients who favorited | ⏳ |
| Toggle Favorite | http://localhost:8000/favori/toggle/1 | POST | Add/remove favorite | ⏳ |
| Check Favorite | http://localhost:8000/favori/check/1 | GET | Is property favorited? | ⏳ |
| Favorite Count | http://localhost:8000/favori/count | GET | Total favorites count | ⏳ |

---

## 🛍️ PROMOTIONS

### Logement-Specific Promotions
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Promotions | http://localhost:8000/proprietaire/logement/1/promotions | GET | Property promotions | ⏳ |
| New Promotion | http://localhost:8000/proprietaire/logement/1/promotions/new | GET/POST | Create promotion | ⏳ |
| Edit Promotion | http://localhost:8000/proprietaire/logement/1/promotions/1/edit | GET/POST | Edit promotion | ⏳ |
| Toggle Promotion | http://localhost:8000/proprietaire/logement/1/promotions/1/toggle | POST | Enable/Disable | ⏳ |
| Delete Promotion | http://localhost:8000/proprietaire/logement/1/promotions/1/delete | POST | Remove promotion | ⏳ |

### Global Promotions
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| My Promotions | http://localhost:8000/proprietaire/promotions | GET | All proprietaire promos | ⏳ |
| Apply All | http://localhost:8000/proprietaire/promotions/appliquer-tous | GET/POST | Apply to all properties | ⏳ |
| Toggle Global | http://localhost:8000/proprietaire/promotions/1/toggle | POST | Enable/Disable global | ⏳ |
| Delete Global | http://localhost:8000/proprietaire/promotions/1/delete | POST | Remove global promo | ⏳ |

---

## 💰 PAYMENT & ORDERS

| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Orders List | http://localhost:8000/commande/ | GET | All orders | ⏳ |
| Order Success | http://localhost:8000/commande/success | GET | Payment successful | ⏳ |
| Order Cancel | http://localhost:8000/commande/cancel | GET | Payment cancelled | ⏳ |
| Invoice | http://localhost:8000/commande/1/facture | GET | Download invoice | ⏳ |

---

## 📊 NOTIFICATIONS & DASHBOARD

### Notifications
| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Admin Notifications | http://localhost:8000/admin/notifications | GET | All notifications | ⏳ |
| View Notification | http://localhost:8000/admin/notification/1 | GET | Notification detail | ⏳ |
| Mark as Read | http://localhost:8000/admin/notifications/marquer-lues | POST | Mark all read | ⏳ |

### Dashboards
| Portal | URL | Expected | Status |
|--------|-----|----------|--------|
| Admin Dashboard | http://localhost:8000/admin | Admin overview | ⏳ |
| Client Dashboard | http://localhost:8000/client | Client portal home | ⏳ |
| Proprietaire Dashboard | http://localhost:8000/proprietaire | Owner portal home | ⏳ |
| Reservation Dashboard | http://localhost:8000/reservation/proprietaire/dashboard | Booking analytics | ⏳ |

---

## 🤖 AI & ADVANCED FEATURES

| Feature | URL | Method | Expected | Status |
|---------|-----|--------|----------|--------|
| Rapport IA | http://localhost:8000/proprietaire/rapport-ia | GET | AI-generated report | ⏳ |
| Face Save | http://localhost:8000/face/save | POST | Save face auth data | ⏳ |
| Face Login | http://localhost:8000/face/login | POST | Face authentication | ⏳ |
| Test SMS | http://localhost:8000/test-sms | GET | SMS notification test | ⏳ |
| SMS Debug | http://localhost:8000/test-sms-debug | GET | Debug SMS service | ⏳ |

---

## ⚙️ SYSTEM & SETUP

| Action | URL | Method | Expected | Status |
|--------|-----|--------|----------|--------|
| Create Admin | http://localhost:8000/create-admin | GET/POST | Admin user creation | ⏳ |

---

## 🧪 TESTING WORKFLOW

### Phase 1: Authentication (FIRST)
```
1. ✅ http://localhost:8000/ → Home loads
2. ✅ http://localhost:8000/login → Login form appears
3. ✅ Register account at /register/client
4. ✅ Log in with credentials
5. ✅ http://localhost:8000/logout → Redirect to home
```

### Phase 2: Core Functionality
```
6. ✅ http://localhost:8000/properties → View listings
7. ✅ http://localhost:8000/property/1 → Property details
8. ✅ http://localhost:8000/reservation/new/1 → Book property
9. ✅ http://localhost:8000/reservation/list → View bookings
10. ✅ http://localhost:8000/mes-favoris → Manage favorites
```

### Phase 3: Forum
```
11. ✅ http://localhost:8000/forum → Forum posts
12. ✅ http://localhost:8000/forum/post/new → Create post
13. ✅ http://localhost:8000/forum/post/1 → View post + comments
14. ✅ Edit & delete own post (buttons should persist after refresh)
15. ✅ http://localhost:8000/forum/test-mail → Email system
```

### Phase 4: Complaints
```
16. ✅ http://localhost:8000/reclamation/new → File complaint
17. ✅ http://localhost:8000/reclamation/list → View complaints
18. ✅ http://localhost:8000/reponse/new/1 → Add response
```

### Phase 5: Proprietaire Features
```
19. ✅ Register as /register/proprietaire
20. ✅ http://localhost:8000/proprietaire/logements → My properties
21. ✅ http://localhost:8000/proprietaire/logement/publier → Publish property
22. ✅ http://localhost:8000/proprietaire/promotions → Create promotions
23. ✅ http://localhost:8000/proprietaire/rapport-ia → View AI report
```

### Phase 6: Admin Panel
```
24. ✅ http://localhost:8000/admin → Dashboard (requires admin role)
25. ✅ http://localhost:8000/admin/user → Manage users
26. ✅ http://localhost:8000/admin/logement/list → Manage properties
27. ✅ http://localhost:8000/admin/forum/post → Manage posts
28. ✅ http://localhost:8000/admin/supervision-reclamation → Review complaints
29. ✅ http://localhost:8000/admin/notifications → View notifications
```

---

## 🐛 TROUBLESHOOTING URLs

If a URL doesn't work:

### 1. **404 Not Found**
- Parameter IDs might be wrong (use actual DB IDs)
- Database not initialized: `docker-compose exec php php bin/console doctrine:migrations:migrate`
- Routes not loaded: Check `config/routes.yaml` or controllers

### 2. **403 Forbidden**
- User doesn't have permission (role-based access)
- CSRF token missing (POST requests need token)
- Session expired → login again

### 3. **500 Server Error**
- Check PHP logs: `docker-compose logs php`
- Run migrations: `php bin/console doctrine:migrations:migrate`
- Clear cache: `php bin/console cache:clear`

### 4. **Connection Refused**
- Docker not running: `docker-compose up -d`
- Database not ready: wait 10 seconds
- Check network: `docker-compose ps`

### 5. **Database Error**
```bash
# Check migrations status
docker-compose exec php php bin/console doctrine:migrations:status

# Run all pending migrations
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Reset database
docker-compose down -v && docker-compose up -d
docker-compose exec php php bin/console doctrine:migrations:migrate
```

---

## 📋 URL Summary by Module

| Module | Total URLs | Status |
|--------|-----------|--------|
| Forum | 15+ | ✅ Ready |
| Property (Logement) | 13+ | ✅ Ready |
| Reservation | 8+ | ✅ Ready |
| Complaints (Reclamation) | 10+ | ✅ Ready |
| Users | 10+ | ✅ Ready |
| Promotions | 8+ | ✅ Ready |
| Admin Panel | 15+ | ✅ Ready |
| Favorites | 6+ | ✅ Ready |
| AI Features | 3+ | ✅ Ready |
| **TOTAL** | **80+** | **✅ Ready** |

---

## ✅ Checklist to Verify All Working

- [ ] Database migrations completed
- [ ] No PHP errors in logs
- [ ] Home page loads
- [ ] Authentication works (login/logout)
- [ ] Forum posts display
- [ ] Can create/edit/delete own posts
- [ ] Can view properties
- [ ] Can make reservations
- [ ] Can file complaints
- [ ] Admin panel accessible
- [ ] All edit/delete buttons persist after refresh

---

**Last Updated:** March 5, 2026  
**Project Status:** ✅ READY FOR TESTING
