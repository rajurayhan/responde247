# Language Support for Homepage

This implementation adds comprehensive language support to the homepage (LandingPage component) with the following features:

## Features

- **Multi-language Support**: English, Spanish, and French
- **Language Switcher**: Elegant dropdown component in header and mobile menu
- **Persistent Language Selection**: Language preference saved in localStorage
- **Browser Language Detection**: Automatically detects user's browser language
- **Fallback Support**: Falls back to English if language not supported
- **Real-time Translation**: Instant language switching without page reload

## Files Added/Modified

### New Files
- `resources/js/lang/en.js` - English translations
- `resources/js/lang/es.js` - Spanish translations  
- `resources/js/lang/fr.js` - French translations
- `resources/js/composables/useLanguage.js` - Language management composable
- `resources/js/components/shared/LanguageSwitcher.vue` - Language switcher component

### Modified Files
- `resources/js/components/landing/LandingPage.vue` - Updated to use translations

## Usage

### Adding New Languages

1. Create a new translation file in `resources/js/lang/` (e.g., `de.js` for German)
2. Add the language to the `languages` object in `useLanguage.js`:

```javascript
const languages = {
  en: { name: 'English', flag: '🇺🇸', translations: en },
  es: { name: 'Español', flag: '🇪🇸', translations: es },
  fr: { name: 'Français', flag: '🇫🇷', translations: fr },
  de: { name: 'Deutsch', flag: '🇩🇪', translations: de } // New language
}
```

### Using Translations in Components

```vue
<script>
import { useLanguage } from '../../composables/useLanguage.js'

export default {
  setup() {
    const { t } = useLanguage()
    
    return {
      t
    }
  }
}
</script>

<template>
  <h1>{{ t('hero.title') }}</h1>
  <p>{{ t('hero.description') }}</p>
</template>
```

### Translation Key Structure

The translation files follow a nested structure:

```javascript
export default {
  nav: {
    features: 'Features',
    pricing: 'Pricing'
  },
  hero: {
    title: 'Transform Your Customer Service',
    benefits: {
      availability: '24/7 Availability'
    }
  }
}
```

Access nested keys with dot notation: `t('hero.benefits.availability')`

## Language Switcher Component

The `LanguageSwitcher` component provides:

- **Visual Language Indicators**: Flag emojis and language names
- **Dropdown Interface**: Clean, accessible dropdown menu
- **Mobile Support**: Responsive design for mobile devices
- **Click Outside to Close**: Intuitive UX behavior

## Browser Integration

- **Document Language**: Updates `document.documentElement.lang` attribute
- **Custom Events**: Dispatches `language-changed` event for other components
- **localStorage**: Persists language preference across sessions

## Future Enhancements

- Add more languages (German, Italian, Portuguese, etc.)
- Implement RTL (Right-to-Left) support for Arabic/Hebrew
- Add language-specific date/time formatting
- Implement pluralization rules
- Add translation management interface for admins

## Testing

To test the language support:

1. Visit the homepage
2. Click the language switcher in the header
3. Select a different language
4. Verify all text updates immediately
5. Refresh the page to confirm language preference persists
6. Test on mobile devices to verify mobile language switcher works
