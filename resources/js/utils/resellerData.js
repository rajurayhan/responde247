/**
 * Reseller Data Utility
 * 
 * Provides easy access to reseller-specific configuration
 * that's available immediately on page load (no API calls needed)
 */

class ResellerDataManager {
    constructor() {
        this.data = window.RESELLER_DATA || {};
        this.isLoaded = !!window.RESELLER_DATA;
    }

    /**
     * Get reseller data value by key with optional fallback
     * @param {string} key - The data key to retrieve
     * @param {*} fallback - Fallback value if key doesn't exist
     * @returns {*} The value or fallback
     */
    get(key, fallback = null) {
        return this.data[key] !== undefined ? this.data[key] : fallback;
    }

    /**
     * Get all reseller data
     * @returns {object} Complete reseller data object
     */
    getAll() {
        return { ...this.data };
    }

    /**
     * Check if reseller data is loaded
     * @returns {boolean} True if data is available
     */
    isDataLoaded() {
        return this.isLoaded;
    }

    // Convenience methods for commonly used data
    getAppName() {
        return this.get('app_name', 'AI Phone System');
    }

    getSlogan() {
        return this.get('slogan', 'Never Miss a Call Again');
    }

    getDescription() {
        return this.get('description', 'AI-powered phone system that answers 24/7');
    }

    getCompanyName() {
        return this.get('company_name', 'AI Phone System');
    }

    getCompanyEmail() {
        return this.get('company_email', 'support@example.com');
    }

    getCompanyPhone() {
        return this.get('company_phone', '+1 (555) 123-4567');
    }

    getLogoUrl() {
        return this.get('logo_url', '/api/saas-public/logo.png');
    }

    getPrimaryColor() {
        return this.get('primary_color', '#4F46E5');
    }

    getSecondaryColor() {
        return this.get('secondary_color', '#10B981');
    }

    getWebsiteUrl() {
        return this.get('website_url', window.location.origin);
    }

    getSupportEmail() {
        return this.get('support_email', this.getCompanyEmail());
    }

    getFooterText() {
        return this.get('footer_text', `© ${new Date().getFullYear()} ${this.getCompanyName()}. All rights reserved.`);
    }

    // Feature toggles
    showDemoRequest() {
        return this.get('show_demo_request', true);
    }

    showContactForm() {
        return this.get('show_contact_form', true);
    }

    showPricing() {
        return this.get('show_pricing', true);
    }

    showTestimonials() {
        return this.get('show_testimonials', true);
    }

    // Social media links
    getFacebookUrl() {
        return this.get('facebook_url', '');
    }

    getTwitterUrl() {
        return this.get('twitter_url', '');
    }

    getLinkedInUrl() {
        return this.get('linkedin_url', '');
    }

    // Legal pages
    getPrivacyPolicyUrl() {
        return this.get('privacy_policy_url', '/privacy');
    }

    getTermsOfServiceUrl() {
        return this.get('terms_of_service_url', '/terms');
    }

    /**
     * Get branding configuration for UI components
     * @returns {object} Branding configuration
     */
    getBrandingConfig() {
        return {
            appName: this.getAppName(),
            slogan: this.getSlogan(),
            description: this.getDescription(),
            logoUrl: this.getLogoUrl(),
            primaryColor: this.getPrimaryColor(),
            secondaryColor: this.getSecondaryColor(),
            companyName: this.getCompanyName(),
            companyEmail: this.getCompanyEmail(),
            companyPhone: this.getCompanyPhone(),
            websiteUrl: this.getWebsiteUrl(),
            supportEmail: this.getSupportEmail(),
            footerText: this.getFooterText()
        };
    }

    /**
     * Get feature flags configuration
     * @returns {object} Feature flags
     */
    getFeatureFlags() {
        return {
            showDemoRequest: this.showDemoRequest(),
            showContactForm: this.showContactForm(),
            showPricing: this.showPricing(),
            showTestimonials: this.showTestimonials()
        };
    }

    /**
     * Get social media links
     * @returns {object} Social media URLs
     */
    getSocialLinks() {
        return {
            facebook: this.getFacebookUrl(),
            twitter: this.getTwitterUrl(),
            linkedin: this.getLinkedInUrl()
        };
    }

    /**
     * Apply CSS custom properties for theming
     */
    applyTheme() {
        if (typeof document !== 'undefined') {
            const root = document.documentElement;
            root.style.setProperty('--primary-color', this.getPrimaryColor());
            root.style.setProperty('--secondary-color', this.getSecondaryColor());
            root.style.setProperty('--reseller-logo-url', `url('${this.getLogoUrl()}')`);
        }
    }
}

// Create singleton instance
const resellerData = new ResellerDataManager();

// Auto-apply theme on load
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => resellerData.applyTheme());
    } else {
        resellerData.applyTheme();
    }
}

export default resellerData;
