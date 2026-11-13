<?php
declare(strict_types=1);

function zeynart_icon(string $name, int $size = 20, string $extraClass = '', float $strokeWidth = 2.0): string
{
    $paths = match ($name) {
        'arrow-up-right' => ['lucide-arrow-up-right', '<path d="M7 7h10v10"></path><path d="M7 17 17 7"></path>'],
        'arrow-right' => ['lucide-arrow-right', '<path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path>'],
        'arrow-left' => ['lucide-arrow-left', '<path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path>'],
        'arrow-down' => ['lucide-arrow-down', '<path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path>'],
        'menu' => ['lucide-menu', '<path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path>'],
        'x' => ['lucide-x', '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>'],
        'play' => ['lucide-play', '<path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"></path>'],
        'pause' => ['lucide-pause', '<rect x="14" y="3" width="5" height="18" rx="1"></rect><rect x="5" y="3" width="5" height="18" rx="1"></rect>'],
        'chevron-right' => ['lucide-chevron-right', '<path d="m9 18 6-6-6-6"></path>'],
        'check' => ['lucide-check', '<path d="M20 6 9 17l-5-5"></path>'],
        'clock-3' => ['lucide-clock-3', '<circle cx="12" cy="12" r="10"></circle><path d="M12 6v6h4"></path>'],
        'search' => ['lucide-search', '<path d="m21 21-4.34-4.34"></path><circle cx="11" cy="11" r="8"></circle>'],
        'code' => ['lucide-code-xml lucide-code-2', '<path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path>'],
        'pen', 'pen-tool' => ['lucide-pen-tool', '<path d="M15.707 21.293a1 1 0 0 1-1.414 0l-1.586-1.586a1 1 0 0 1 0-1.414l5.586-5.586a1 1 0 0 1 1.414 0l1.586 1.586a1 1 0 0 1 0 1.414z"></path><path d="m18 13-1.375-6.874a1 1 0 0 0-.746-.776L3.235 2.028a1 1 0 0 0-1.207 1.207L5.35 15.879a1 1 0 0 0 .776.746L13 18"></path><path d="m2.3 2.3 7.286 7.286"></path><circle cx="11" cy="11" r="2"></circle>'],
        'layers' => ['lucide-layers lucide-layers-3', '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"></path><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"></path><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"></path>'],
        'phone-device', 'smartphone' => ['lucide-smartphone', '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"></rect><path d="M12 18h.01"></path>'],
        'cloud' => ['lucide-cloud', '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>'],
        'cart', 'shopping-cart' => ['lucide-shopping-cart', '<path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"></path><path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"></path><circle cx="18" cy="20" r="2"></circle><circle cx="8" cy="20" r="2"></circle>'],
        'scan-search' => ['lucide-scan-search', '<path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><circle cx="12" cy="12" r="3"></circle><path d="m16 16-1.9-1.9"></path>'],
        'route' => ['lucide-route', '<circle cx="6" cy="19" r="3"></circle><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"></path><circle cx="18" cy="5" r="3"></circle>'],
        'rocket' => ['lucide-rocket', '<path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09"></path><path d="M9 12a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.4 22.4 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 .05 5 .05"></path>'],
        'compass' => ['lucide-compass', '<circle cx="12" cy="12" r="10"></circle><path d="m16.24 7.76-1.804 5.411a2 2 0 0 1-1.265 1.265L7.76 16.24l1.804-5.411a2 2 0 0 1 1.265-1.265z"></path>'],
        'gem' => ['lucide-gem', '<path d="M10.5 3 8 9l4 13 4-13-2.5-6"></path><path d="M17 3a2 2 0 0 1 1.6.8l3 4a2 2 0 0 1 .013 2.382l-7.99 10.986a2 2 0 0 1-3.247 0l-7.99-10.986A2 2 0 0 1 2.4 7.8l2.998-3.997A2 2 0 0 1 7 3z"></path><path d="M2 9h20"></path>'],
        'handshake' => ['lucide-handshake', '<path d="m11 17 2 2a1 1 0 1 0 3-3"></path><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"></path><path d="m21 3 1 11h-2"></path><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"></path><path d="M3 4h8"></path>'],
        'palette' => ['lucide-palette', '<path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"></path><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"></circle><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"></circle><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"></circle><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"></circle>'],
        'type' => ['lucide-type', '<path d="M12 4v16"></path><path d="M4 7V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2"></path><path d="M9 20h6"></path>'],
        'shield-check' => ['lucide-shield-check', '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path>'],
        'phone' => ['lucide-phone', '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384"></path>'],
        'mail' => ['lucide-mail', '<path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"></path><rect x="2" y="4" width="20" height="16" rx="2"></rect>'],
        'send' => ['lucide-send', '<path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"></path><path d="m21.854 2.147-10.94 10.939"></path>'],
        'sparkles' => ['lucide-sparkles', '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path>'],
        'globe' => ['lucide-globe', '<circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path>'],
        'map-pin' => ['lucide-map-pin', '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path><circle cx="12" cy="10" r="3"></circle>'],
        default => ['lucide-arrow-up-right', '<path d="M7 7h10v10"></path><path d="M7 17 17 7"></path>'],
    };

    $cls = trim('lucide ' . $paths[0] . ($extraClass !== '' ? ' ' . $extraClass : ''));
    $sw = rtrim(rtrim(number_format($strokeWidth, 2, '.', ''), '0'), '.');
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $sw . '" stroke-linecap="round" stroke-linejoin="round" class="' . e($cls) . '" aria-hidden="true">' . $paths[1] . '</svg>';
}

function zeynart_social_icon(string $platform, int $size = 18): string
{
    return match ($platform) {
        'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>',
        'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 13.5h2.5l1-4H14v-2c0-1.03 0-2 2-2h1.5V2.14c-.326-.043-1.557-.14-2.857-.14C11.928 2 10 3.657 10 6.7v2.8H7v4h3V22h4v-8.5z"></path></svg>',
        'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.94 5a2 2 0 1 1-4-.002 2 2 0 0 1 4 .002zM7 8.48H3V21h4V8.48zm6.32 0H9.34V21h3.94v-6.57c0-3.66 4.77-4 4.77 0V21H22v-7.93c0-6.17-7.06-5.94-8.72-2.91l.04-1.68z"></path></svg>',
        'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>',
        'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path></svg>',
        'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.031 2c-5.506 0-9.989 4.478-9.99 9.984a9.964 9.964 0 0 0 1.333 4.993L2 22l5.233-1.237a9.994 9.994 0 0 0 4.779 1.217h.004c5.505 0 9.988-4.478 9.989-9.984 0-2.669-1.037-5.176-2.922-7.062A9.935 9.935 0 0 0 12.031 2zm4.96 14.212c-.208.583-1.222 1.145-1.685 1.186-.458.042-.887.207-2.995-.624-2.537-1-4.169-3.601-4.296-3.767-.125-.167-1.025-1.362-1.025-2.596 0-1.235.649-1.84.879-2.09.23-.25.5-.312.667-.312.167 0 .333.002.479.009.153.007.358-.058.56.427.208.5.708 1.729.77 1.854.063.125.105.271.021.438-.083.166-.125.27-.25.416-.125.146-.262.326-.375.438-.125.125-.255.26-.11.51.146.25.648 1.068 1.391 1.73.956.852 1.762 1.116 2.012 1.241.25.125.396.104.542-.062.146-.167.625-.729.791-.979.167-.25.334-.208.563-.125.229.083 1.458.688 1.708.813.25.125.417.187.479.291.062.104.062.604-.146 1.187z"></path></svg>',
        'tiktok' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"></path></svg>',
        'behance' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 7h-7V5h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-3.074 0-5.564-1.729-5.564-5.675 0-3.91 2.325-5.92 5.466-5.92 3.082 0 4.964 1.782 5.375 4.426.078.506.109 1.188.095 2.14H15.97c.13 3.211 3.483 3.312 4.588 2.029h3.168zm-7.686-4h4.965c-.105-1.547-1.136-2.219-2.477-2.219-1.466 0-2.277.768-2.488 2.219zm-9.574 6.988H0V5.021h6.953c5.476.081 5.58 5.444 2.72 6.906 3.461 1.26 3.577 8.061-3.207 8.061zM3 11h3.584c2.508 0 2.906-3-.312-3H3v3zm3.391 3H3v3.016h3.341c3.055 0 2.868-3.016.05-3.016z"></path></svg>',
        'dribbble' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M19.13 5.09C15.22 9.14 10 10.44 2.25 10.94"></path><path d="M21.75 12.84c-6.62-1.41-12.14 1-16.38 6.32"></path><path d="M8.56 2.75c4.37 6 6 9.42 8 17.72"></path></svg>',
        'github' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"></path></svg>',
        'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.017 2C6.486 2 2 6.486 2 12.017c0 4.242 2.636 7.868 6.357 9.327-.088-.792-.167-2.006.035-2.87.182-.78 1.174-4.976 1.174-4.976s-.3-.6-.3-1.486c0-1.392.807-2.431 1.812-2.431.854 0 1.267.641 1.267 1.41 0 .859-.547 2.144-.83 3.335-.236.998.5 1.812 1.484 1.812 1.781 0 3.15-1.878 3.15-4.589 0-2.399-1.724-4.077-4.186-4.077-2.852 0-4.525 2.139-4.525 4.35 0 .861.332 1.785.746 2.287a.3.3 0 0 1 .069.288c-.076.316-.244.998-.277 1.137-.044.183-.145.222-.334.134-1.249-.581-2.03-2.407-2.03-3.874 0-3.154 2.292-6.052 6.608-6.052 3.469 0 6.165 2.473 6.165 5.776 0 3.447-2.173 6.22-5.19 6.22-1.013 0-1.965-.526-2.291-1.148l-.623 2.378c-.226.869-.835 1.958-1.244 2.621.937.29 1.931.446 2.962.446 5.531 0 10.017-4.486 10.017-10.017C22.034 6.486 17.548 2 12.017 2z"></path></svg>',
        'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"></path></svg>',
        default => zeynart_icon('globe', $size, '', 1.85),
    };
}

function zeynart_parse_social_url(string $rawUrl): ?array
{
    $url = trim($rawUrl);
    if ($url === '') {
        return null;
    }
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));
    $host = preg_replace('/^www\./', '', $host) ?? $host;
    if ($host === '') {
        return null;
    }

    $path = trim((string)(parse_url($url, PHP_URL_PATH) ?? ''), '/');
    $segments = $path !== '' ? array_values(array_filter(explode('/', $path))) : [];
    $lastSeg = $segments !== [] ? (string)end($segments) : '';

    [$platform, $label] = match (true) {
        str_contains($host, 'instagram.com') || $host === 'instagr.am' => ['instagram', 'Instagram'],
        str_contains($host, 'facebook.com') || $host === 'fb.com' || $host === 'fb.me' => ['facebook', 'Facebook'],
        str_contains($host, 'linkedin.com') || $host === 'lnkd.in' => ['linkedin', 'LinkedIn'],
        $host === 'x.com' || str_contains($host, 'twitter.com') => ['x', 'X (Twitter)'],
        str_contains($host, 'youtube.com') || $host === 'youtu.be' => ['youtube', 'YouTube'],
        $host === 'wa.me' || str_contains($host, 'whatsapp.com') => ['whatsapp', 'WhatsApp'],
        str_contains($host, 'tiktok.com') => ['tiktok', 'TikTok'],
        str_contains($host, 'behance.net') => ['behance', 'Behance'],
        str_contains($host, 'dribbble.com') => ['dribbble', 'Dribbble'],
        str_contains($host, 'github.com') => ['github', 'GitHub'],
        str_contains($host, 'pinterest.') || $host === 'pin.it' => ['pinterest', 'Pinterest'],
        $host === 't.me' || str_contains($host, 'telegram.') => ['telegram', 'Telegram'],
        default => ['globe', ucfirst((string)explode('.', $host)[0])],
    };

    $handle = $label;
    if ($lastSeg !== '' && !in_array(strtolower($lastSeg), ['send', 'watch', 'channel', 'share'], true)) {
        $decoded = rawurldecode($lastSeg);
        if ($platform === 'whatsapp' && ctype_digit(ltrim($decoded, '+'))) {
            $handle = '+' . ltrim($decoded, '+');
        } elseif (in_array($platform, ['instagram', 'x', 'tiktok', 'youtube', 'telegram'], true)) {
            $handle = '@' . ltrim($decoded, '@');
        } else {
            $handle = $decoded;
        }
    }

    return [
        'url' => $url,
        'platform' => $platform,
        'label' => $label,
        'handle' => $handle,
    ];
}

function zeynart_social_links(array $store): array
{
    $rawList = [];
    if (!empty($store['seo']['social_profiles']) && is_array($store['seo']['social_profiles'])) {
        foreach ($store['seo']['social_profiles'] as $u) {
            if (is_string($u)) {
                $rawList[] = $u;
            }
        }
    }
    if (!empty($store['site']['social_profiles']) && is_array($store['site']['social_profiles'])) {
        foreach ($store['site']['social_profiles'] as $u) {
            if (is_string($u)) {
                $rawList[] = $u;
            }
        }
    }

    $seen = [];
    $result = [];
    foreach ($rawList as $raw) {
        $parsed = zeynart_parse_social_url($raw);
        if ($parsed === null) {
            continue;
        }
        $normKey = strtolower(rtrim($parsed['url'], '/'));
        if (isset($seen[$normKey])) {
            continue;
        }
        $seen[$normKey] = true;
        $result[] = $parsed;
    }

    return $result;
}
