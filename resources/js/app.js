import './bootstrap';
import { createApp, h } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { updateDocumentTitle } from './utils/systemSettings.js'
import App from './App.vue';

// Import components
import Login from './components/auth/Login.vue';
import Register from './components/auth/Register.vue';
import RegisterReseller from './components/auth/RegisterReseller.vue';
import ForgotPassword from './components/auth/ForgotPassword.vue';
import ResetPassword from './components/auth/ResetPassword.vue';
import Dashboard from './components/dashboard/Dashboard.vue';
import Profile from './components/profile/Profile.vue';
// Lazy load admin components for better performance
import AdminDashboard from './components/admin/AdminDashboard.vue';
const AdminUsers = () => import('./components/admin/Users.vue');
const AdminResellers = () => import('./components/admin/Resellers.vue');
const SuperAdminDashboard = () => import('./components/admin/SuperAdminDashboard.vue');
const AdminAssistants = () => import('./components/admin/Assistants.vue');
const AdminFeatures = () => import('./components/admin/Features.vue');
const AdminPackages = () => import('./components/admin/Packages.vue');
const AdminSubscriptions = () => import('./components/admin/Subscriptions.vue');
const UsageOverview = () => import('./components/admin/UsageOverview.vue');
const Templates = () => import('./components/admin/Templates.vue');
import LandingPage from './components/landing/LandingPage.vue';
import AssistantForm from './components/assistant/AssistantForm.vue';
import UserAssistants from './components/dashboard/UserAssistants.vue';
import Pricing from './components/pricing/Pricing.vue';
import SubscriptionManager from './components/subscription/SubscriptionManager.vue';
// Lazy load more components for better performance
const TransactionHistory = () => import('./components/transactions/TransactionHistory.vue');
const PaymentForm = () => import('./components/transactions/PaymentForm.vue');
const TransactionManagement = () => import('./components/admin/TransactionManagement.vue');
const DemoRequestForm = () => import('./components/demo/DemoRequestForm.vue');
const DemoRequests = () => import('./components/admin/DemoRequests.vue');
const ResellerSettings = () => import('./components/admin/ResellerSettings.vue');
const DebugInfo = () => import('./components/admin/DebugInfo.vue');
const StripeConfiguration = () => import('./components/admin/StripeConfiguration.vue');
const CallLogsPage = () => import('./components/call-logs/CallLogsPage.vue');
const CallLogDetailsPage = () => import('./components/call-logs/CallLogDetailsPage.vue');
const AdminCallLogDetailsPage = () => import('./components/admin/AdminCallLogDetailsPage.vue');
const AdminCallLogs = () => import('./components/admin/CallLogs.vue');
const ContactManagement = () => import('./components/admin/ContactManagement.vue');
const ResellerBilling = () => import('./components/reseller/ResellerBilling.vue');
const TermsOfService = () => import('./components/shared/TermsOfService.vue');
const PrivacyPolicy = () => import('./components/shared/PrivacyPolicy.vue');
const NotFound = () => import('./components/shared/NotFound.vue');
const PaymentSuccess = () => import('./components/subscription/PaymentSuccess.vue');

// Set initial document title
updateDocumentTitle()

// Create a conditional dashboard component
const ConditionalDashboard = {
    name: 'ConditionalDashboard',
    components: {
        Dashboard,
        AdminDashboard
    },
    computed: {
        isAdmin() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            return user.role === 'admin' || user.role === 'reseller_admin';
        }
    },
    template: `
        <div>
            <AdminDashboard v-if="isAdmin" />
            <Dashboard v-else />
        </div>
    `
};

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'landing',
            component: Login,
            meta: { requiresGuest: true }
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { requiresGuest: true }
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
            meta: { requiresGuest: true }
        },
        {
            path: '/reseller-registration',
            name: 'reseller-registration',
            component: RegisterReseller,
            meta: { requiresGuest: true }
        },
        {
            path: '/reseller-registration/success',
            name: 'reseller-registration-success',
            component: () => import('./components/auth/ResellerRegistrationSuccess.vue'),
            meta: { requiresGuest: true }
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPassword,
            meta: { requiresGuest: true }
        },
        {
            path: '/password-reset/:token',
            name: 'reset-password',
            component: ResetPassword,
            meta: { requiresGuest: true }
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: ConditionalDashboard,
            meta: { requiresAuth: true }
        },
        {
            path: '/profile',
            name: 'profile',
            component: Profile,
            meta: { requiresAuth: true }
        },
        {
            path: '/admin',
            name: 'admin',
            component: AdminDashboard,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/users',
            name: 'admin-users',
            component: AdminUsers,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        // Super Admin Routes
        {
            path: '/super-admin',
            name: 'super-admin',
            component: SuperAdminDashboard,
            meta: { requiresAuth: true, requiresSuperAdmin: true }
        },
        {
            path: '/super-admin/resellers',
            name: 'super-admin-resellers',
            component: AdminResellers,
            meta: { requiresAuth: true, requiresSuperAdmin: true }
        },
        {
            path: '/super-admin/resellers/:reseller_id',
            name: 'super-admin-reseller-details',
            component: () => import('./components/admin/ResellerDetails.vue'),
            meta: { requiresAuth: true, requiresSuperAdmin: true }
        },
        {
            path: '/super-admin/reseller-packages',
            name: 'super-admin-reseller-packages',
            component: () => import('./components/admin/ResellerPackages.vue'),
            meta: { requiresAuth: true, requiresSuperAdmin: true }
        },
    {
        path: '/super-admin/reseller-subscriptions',
        name: 'super-admin-reseller-subscriptions',
        component: () => import('./components/admin/ResellerSubscriptions.vue'),
        meta: { requiresAuth: true, requiresSuperAdmin: true }
    },
        {
            path: '/super-admin/reseller-transactions',
            name: 'super-admin-reseller-transactions',
            component: () => import('./components/admin/ResellerTransactions.vue'),
            meta: { requiresAuth: true, requiresSuperAdmin: true }
        },
        {
            path: '/super-admin/usage-reports',
            name: 'super-admin-usage-reports',
            component: () => import('./components/admin/UsageReport.vue'),
            meta: { requiresAuth: true, requiresSuperAdmin: true }
        },
        {
            path: '/admin/assistants',
            name: 'admin-assistants',
            component: AdminAssistants,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/transactions',
            name: 'admin-transactions',
            component: TransactionManagement,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/features',
            name: 'admin-features',
            component: AdminFeatures,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/packages',
            name: 'admin-packages',
            component: AdminPackages,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/usage-overview',
            name: 'usage-overview',
            component: UsageOverview,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/subscriptions',
            name: 'admin-subscriptions',
            component: AdminSubscriptions,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/templates',
            name: 'admin-templates',
            component: Templates,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/demo-requests',
            name: 'admin-demo-requests',
            component: DemoRequests,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/system-settings',
            name: 'admin-system-settings',
            component: ResellerSettings,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/stripe-configuration',
            name: 'admin-stripe-configuration',
            component: StripeConfiguration,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/debug',
            name: 'debug',
            component: DebugInfo,
            meta: { requiresAuth: true }
        },
        {
            path: '/assistants',
            name: 'user-assistants',
            component: UserAssistants,
            meta: { requiresAuth: true }
        },
        {
            path: '/assistants/create',
            name: 'assistant-create',
            component: AssistantForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/assistants/:id/edit',
            name: 'assistant-edit',
            component: AssistantForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/pricing',
            name: 'pricing',
            component: Pricing
        },
        {
            path: '/subscription',
            name: 'subscription',
            component: SubscriptionManager,
            meta: { requiresAuth: true }
        },
        {
            path: '/subscription/success',
            name: 'subscription-success',
            component: PaymentSuccess,
            meta: { requiresAuth: true }
        },
        {
            path: '/transactions',
            name: 'transaction-history',
            component: TransactionHistory,
            meta: { requiresAuth: true }
        },
        {
            path: '/payment',
            name: 'payment',
            component: PaymentForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/demo-request',
            name: 'demo-request',
            component: DemoRequestForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/call-logs',
            name: 'call-logs',
            component: CallLogsPage,
            meta: { requiresAuth: true }
        },
        {
            path: '/call-logs/:call_id',
            name: 'call-log-details',
            component: CallLogDetailsPage,
            meta: { requiresAuth: true }
        },
        {
            path: '/admin/call-logs',
            name: 'admin-call-logs',
            component: AdminCallLogs,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/call-logs/:call_id',
            name: 'admin-call-log-details',
            component: AdminCallLogDetailsPage,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/contacts',
            name: 'admin-contacts',
            component: ContactManagement,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/reseller-billing',
            name: 'reseller-billing',
            component: ResellerBilling,
            meta: { requiresAuth: true, requiresResellerAdmin: true }
        },
        {
            path: '/terms',
            name: 'terms',
            component: TermsOfService
        },
        {
            path: '/privacy',
            name: 'privacy',
            component: PrivacyPolicy
        },
        {
            path: '/payment-success',
            name: 'payment-success',
            component: PaymentSuccess,
            meta: { requiresAuth: true }
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFound
        }
    ]
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const isAdmin = user.role === 'admin' || user.role === 'reseller_admin';
    const isSuperAdmin = user.role === 'super_admin' || user.role === 'admin'; // Admin can also access super admin features
    const isResellerAdmin = user.role === 'reseller_admin';

    // Check hostname restriction for reseller registration
    if (to.name === 'reseller-registration') {
        const hostname = window.location.hostname;
        const allowedHosts = ['localhost', 'responde247.com'];
        
        console.log('Reseller registration access check:', {
            hostname,
            allowedHosts,
            isAllowed: allowedHosts.includes(hostname)
        });
        
        if (!allowedHosts.includes(hostname)) {
            // Redirect to 404 page for unauthorized hosts
            console.log('Access denied for hostname:', hostname);
            next('/not-found');
            return;
        }
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
        // Store the intended destination for redirect after login/registration
        localStorage.setItem('intendedUrl', to.fullPath);
        
        // Special handling for payment route - redirect to register instead of login
        if (to.name === 'payment') {
            next('/register');
        } else {
            next('/login');
        }
    } else if (to.meta.requiresGuest && isAuthenticated) {
        // Check if user came from a payment flow and redirect accordingly
        const intendedUrl = localStorage.getItem('intendedUrl');
        if (intendedUrl && intendedUrl.includes('/payment')) {
            localStorage.removeItem('intendedUrl');
            next(intendedUrl);
        } else {
            next('/dashboard');
        }
    } else if (to.meta.requiresSuperAdmin && !isSuperAdmin) {
        next('/dashboard');
    } else if (to.meta.requiresAdmin && !isAdmin) {
        next('/dashboard');
    } else if (to.meta.requiresResellerAdmin && !isResellerAdmin) {
        next('/dashboard');
    } else {
        next();
    }
});

// Global afterEach hook to update document titles
router.afterEach((to) => {
    // Map route names to page titles
    const pageTitles = {
        'landing': 'Home',
        'login': 'Sign In',
        'register': 'Sign Up',
        'reseller-registration': 'Become a Reseller',
        'reseller-registration-success': 'Registration Successful',
        'forgot-password': 'Forgot Password',
        'reset-password': 'Reset Password',
        'dashboard': 'Dashboard',
        'admin': 'Admin Dashboard',
        'admin-users': 'User Management',
        'admin-assistants': 'Assistant Management',
        'admin-transactions': 'Transaction Management',
        'admin-features': 'Feature Management',
        'admin-packages': 'Package Management',
        'usage-overview': 'Usage Overview',
        'admin-subscriptions': 'Subscription Management',
        'admin-templates': 'Template Management',
        'admin-demo-requests': 'Demo Requests',
        'admin-system-settings': 'System Settings',
        'admin-call-logs': 'Call Logs',
        'admin-call-log-details': 'Call Details',
        'admin-contacts': 'Contact Management',
        'user-assistants': 'My Assistants',
        'assistant-create': 'Create Assistant',
        'assistant-edit': 'Edit Assistant',
        'profile': 'Profile',
        'pricing': 'Pricing',
        'subscription': 'Subscription',
        'transaction-history': 'Transaction History',
        'payment': 'Payment',
        'demo-request': 'Request Demo',
        'call-logs': 'Call Logs',
        'call-log-details': 'Call Details',
        'reseller-billing': 'Billing and Subscription',
        'terms': 'Terms of Service',
        'privacy': 'Privacy Policy',
        'payment-success': 'Payment Successful',
        'not-found': 'Page Not Found'
    };

    const pageTitle = pageTitles[to.name] || to.name;
    updateDocumentTitle(pageTitle);
});

// Create Vue app
const app = createApp(App);
app.use(router);
app.mount('#app'); 