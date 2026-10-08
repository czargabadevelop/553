import { createContext, useContext, useEffect, useState } from 'react';

const ThemeContext = createContext(null);

/**
 * Reads the persisted dark mode flag. Mirrors the Blade `darkMode` Alpine property.
 */
function readStoredDarkMode() {
  if (typeof window === 'undefined') {
    return false;
  }

  try {
    return JSON.parse(localStorage.getItem('darkMode') || 'false') === true;
  } catch (error) {
    return false;
  }
}

export function ThemeProvider({ children }) {
  const [isDark, setIsDark] = useState(readStoredDarkMode);

  useEffect(() => {
    document.documentElement.classList.toggle('dark', isDark);

    try {
      localStorage.setItem('darkMode', JSON.stringify(isDark));
    } catch (error) {
      // Storage can be unavailable (private mode); dark mode simply won't persist.
    }
  }, [isDark]);

  const value = {
    isDark,
    toggle: () => setIsDark((current) => !current),
  };

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  return useContext(ThemeContext);
}
