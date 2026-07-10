  <script data-turbo-eval="false">    /* fixes white flash */
    const getPreferredTheme = () => {
      if (localStorage.getItem('theme')) {
        return localStorage.getItem('theme')
      }
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = (theme) => {
      if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-theme', 'dark')
      } else {
        document.documentElement.setAttribute('data-theme', theme);
      }
    }

    setTheme(getPreferredTheme())

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      const storedTheme = localStorage.getItem('theme');
      if (storedTheme !== 'light' && storedTheme !== 'dark') {
        setTheme(getPreferredTheme())
      }
    })

    function toggletheme() {
      const theme = getPreferredTheme() === 'dark' ? 'light' : 'dark'
      document.documentElement.setAttribute('data-theme', theme)
      localStorage.setItem('theme', theme)
      setTheme(theme)
    }    
  </script>
