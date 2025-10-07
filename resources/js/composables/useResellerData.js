/**
 * Vue Composable for Reseller Data
 * 
 * Provides reactive access to reseller configuration in Vue components
 */

import { ref, computed, readonly } from 'vue';
import resellerData from '../utils/resellerData.js';

// Global reactive state
const resellerState = ref(resellerData.getAll());
const isLoaded = ref(resellerData.isDataLoaded());

export function useResellerData() {
    // Computed properties for commonly used values
    const appName = computed(() => resellerState.value.app_name || 'AI Phone System');
    const slogan = computed(() => resellerState.value.slogan || 'Never Miss a Call Again');
    const description = computed(() => resellerState.value.description || 'AI-powered phone system that answers 24/7');
    const companyName = computed(() => resellerState.value.company_name || 'AI Phone System');
    const companyEmail = computed(() => resellerState.value.company_email || 'support@example.com');
    const companyPhone = computed(() => resellerState.value.company_phone || '+1 (555) 123-4567');
    const logoUrl = computed(() => resellerState.value.logo_url || '/api/saas-public/logo.png');
    const primaryColor = computed(() => resellerState.value.primary_color || '#4F46E5');
    const secondaryColor = computed(() => resellerState.value.secondary_color || '#10B981');
    const websiteUrl = computed(() => resellerState.value.website_url || window.location.origin);
    const supportEmail = computed(() => resellerState.value.support_email || companyEmail.value);
    const footerText = computed(() => resellerState.value.footer_text || `© ${new Date().getFullYear()} ${companyName.value}. All rights reserved.`);

    // Feature flags
    const features = computed(() => ({
        showDemoRequest: resellerState.value.show_demo_request !== false,
        showContactForm: resellerState.value.show_contact_form !== false,
        showPricing: resellerState.value.show_pricing !== false,
        showTestimonials: resellerState.value.show_testimonials !== false
    }));

    // Social media links
    const socialLinks = computed(() => ({
        facebook: resellerState.value.facebook_url || '',
        twitter: resellerState.value.twitter_url || '',
        linkedin: resellerState.value.linkedin_url || ''
    }));

    // Legal pages
    const legalPages = computed(() => ({
        privacy: resellerState.value.privacy_policy_url || '/privacy',
        terms: resellerState.value.terms_of_service_url || '/terms'
    }));

    // Additional computed properties
    const bannerUrl = computed(() => resellerState.value.homepage_banner || resellerState.value.banner_url || '');

    // Branding configuration object
    const branding = computed(() => ({
        appName: appName.value,
        slogan: slogan.value,
        description: description.value,
        logoUrl: logoUrl.value,
        bannerUrl: bannerUrl.value,
        primaryColor: primaryColor.value,
        secondaryColor: secondaryColor.value,
        companyName: companyName.value,
        companyEmail: companyEmail.value,
        companyPhone: companyPhone.value,
        websiteUrl: websiteUrl.value,
        supportEmail: supportEmail.value,
        footerText: footerText.value
    }));

    // Methods
    const getValue = (key, fallback = null) => {
        return resellerState.value[key] !== undefined ? resellerState.value[key] : fallback;
    };

    const updateData = (newData) => {
        resellerState.value = { ...resellerState.value, ...newData };
    };

    const refreshData = () => {
        if (window.RESELLER_DATA) {
            resellerState.value = { ...window.RESELLER_DATA };
            isLoaded.value = true;
        }
    };

    // CSS custom properties for theming
    const applyTheme = () => {
        if (typeof document !== 'undefined') {
            const root = document.documentElement;
            root.style.setProperty('--primary-color', primaryColor.value);
            root.style.setProperty('--secondary-color', secondaryColor.value);
            root.style.setProperty('--reseller-logo-url', `url('${logoUrl.value}')`);
        }
    };

    return {
        // State
        isLoaded: readonly(isLoaded),
        data: readonly(resellerState),

        // Individual properties
        appName,
        slogan,
        description,
        companyName,
        companyEmail,
        companyPhone,
        logoUrl,
        bannerUrl,
        primaryColor,
        secondaryColor,
        websiteUrl,
        supportEmail,
        footerText,

        // Grouped properties
        branding,
        features,
        socialLinks,
        legalPages,

        // Methods
        getValue,
        updateData,
        refreshData,
        applyTheme
    };
}

// Auto-apply theme
export function useResellerTheme() {
    const { applyTheme, primaryColor, secondaryColor, logoUrl } = useResellerData();
    
    // Apply theme on component mount
    applyTheme();

    return {
        primaryColor,
        secondaryColor,
        logoUrl,
        applyTheme
    };
}
