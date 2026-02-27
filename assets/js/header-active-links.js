document.addEventListener('DOMContentLoaded', function () {

    function normalize(p) {
      return p.replace(/\/+$/, '') || '/';
    }
  
    var current = normalize(location.pathname);
  
    var rules = [
      { selector: 'a[href="/"]', match: ['/'] },
      { selector: '#communityDropdown', match: ['/community','/community/profile','/community/submit-post'] },
      { selector: 'a[href="/about"]', match: ['/about'] },
      { selector: 'a[href="/blog"]', match: ['/blog'] },
      { selector: 'a[href="/career"]', match: ['/career'] },
      { selector: 'a[href="/contact"]', match: ['/contact'] },
      // Dropdown parent: Destination
      {
        selector: '.nav-item.dropdown > a.dropdown-toggle',
        match: ['/destination', '/region', '/south-korea', '/japan', '/thailand']
      }
    ];
  
    // 1. Reset everything first
    document.querySelectorAll('.navbar-nav .nav-link, .dropdown-item')
      .forEach(function (el) {
        el.classList.remove('active','is-current');
        el.removeAttribute('aria-current');
        el.removeAttribute('aria-disabled');
      });
  
    // 2. Apply rules for "Active" parenting (visual highlight)
    rules.forEach(function (rule) {
      if (rule.match.some(function (m) {
        // Special case for Home: strict equality only
        if (m === '/') {
           return current === '/';
        }
        // Others: Exact match OR sub-path match
        return current === m || current.startsWith(m + '/');
      })) {
        document.querySelectorAll(rule.selector).forEach(function (el) {
          el.classList.add('active');
        });
      }
    });
  
    // 3. Exact match disabling (Current Page Logic)
    var links = document.querySelectorAll('.navbar-nav .nav-link, .dropdown-item');
    links.forEach(function (a) {
      // Ignore anchors or empty links
      var href = a.getAttribute('href');
      if (!href || href === '#' || href.startsWith('javascript')) return;
  
      try {
        var url = new URL(a.href, location.origin);
        // Compare normalized paths
        if (normalize(url.pathname) === current) {
          // It's the current page
          a.classList.add('is-current');
          a.setAttribute('aria-current', 'page');
          a.setAttribute('aria-disabled', 'true');
          
          // Visual cues
          a.style.pointerEvents = 'none';
          a.style.cursor = 'default';
          
          // Hard disable click
          a.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
          });
        }
      } catch (e) {
        // ignore invalid URLs
      }
    });
  });
  
