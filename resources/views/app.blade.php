<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon & App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Structured Data (Example: JSON-LD Schema.org) -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "Larasonic",
            "url": "https://larasonic.com/",
            "image": "https://larasonic.com/images/og.webp",
            "description": "A modern Laravel SaaS starter kit for the VILT stack. Clone the repo, start building scalable and maintainable applications quickly.",
            "applicationCategory": "DeveloperTool",
            "operatingSystem": "All",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "USD",
                "category": "Free"
            }
        }
    </script>

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    <!-- Chatwoot Script -->

    <script>
      window.chatwootSettings = {"position":"right","type":"expanded_bubble","launcherTitle":"Suporte Tanavitrine"};
      (function(d,t) {
        var BASE_URL="https://chat.gravithub.com/";
        var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
        g.src=BASE_URL+"/packs/js/sdk.js";
        g.defer = true;
        g.async = true;
        s.parentNode.insertBefore(g,s);
        g.onload=function(){
          window.chatwootSDK.run({
            websiteToken: '4nvUpsnQmHj9bvANudqdpSQf',
            baseUrl: BASE_URL
          })
        }
      })(document,"script");
    </script>

</body>

</html>
