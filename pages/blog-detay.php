<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/page_builder.php';

/** @var array $activePost */
$post = $activePost;
$curLang = zeynart_current_lang();
$langMeta = zeynart_lang_meta($curLang);
$slug = (string)($post['slug'] ?? '');
$baseSlug = (string)($post['base_slug'] ?? $slug);
$title = (string)($post['title'] ?? '');
$blogBaseSlug = zeynart_page_slug($store, 'blog') ?: 'blog';
$treeCategories = zeynart_build_blog_category_tree(zeynart_get_blog_categories($store));
$resolvedPostCats = zeynart_resolve_post_categories($post, $treeCategories);
$primaryPostCat = $resolvedPostCats[0] ?? [
    'id' => 'cat_genel',
    'name' => t_category((string)($post['category'] ?? 'Genel'), $curLang),
    'slug' => slugify((string)($post['category'] ?? 'genel')),
    'ancestor_breadcrumbs' => [],
];
$category = (string)($primaryPostCat['name'] ?? t_category('Genel', $curLang));
$postCatSlug = (string)($primaryPostCat['slug'] ?? 'genel');
$postCatIdsSet = array_flip(array_map(static fn(array $c): string => (string)$c['id'], $resolvedPostCats));
$catBySlugMap = [];
foreach ($treeCategories as $tcItem) {
    $catBySlugMap[(string)$tcItem['slug']] = $tcItem;
}
$excerpt = (string)($post['excerpt'] ?? '');
$dateIso = (string)($post['date'] ?? date('Y-m-d'));
$dateDisplay = (string)($post['date_display'] ?? format_localized_date($dateIso));
$updatedIso = (string)($post['updated_at'] ?? $dateIso);
$readTime = (string)($post['read'] ?? localize_reading_time('3 dk'));
$coverImg = (string)($post['image'] ?? '/images/journal/web-planning.webp');
$rawCoverAlt = trim((string)($post['image_alt'] ?? ''));
// Clean raw UUID/hash alt text automatically
if ($rawCoverAlt === '' || preg_match('/^[0-9a-f]{6,}[\s\-_0-9a-f]{8,}$/i', $rawCoverAlt)) {
    $coverAlt = $title;
} else {
    $coverAlt = $rawCoverAlt;
}
$seo = $post['seo'] ?? [];
$toc = is_array($post['toc'] ?? null) ? $post['toc'] : [];
$srvLink = is_array($post['service_link'] ?? null) ? $post['service_link'] : ['href' => '/hizmetler/', 'label' => t('Hizmetlerimiz')];
$faqs = is_array($post['faqs'] ?? null) ? $post['faqs'] : [];
$contentHtml = (string)($post['content_html'] ?? '');
$proseMode = (string)($post['prose_mode'] ?? 'html');

// Resolve related posts (prioritize explicit related_slugs, then same category, then recent — up to 3 cards)
$blogIndex = load_blog_index();
$indexBySlug = [];
foreach ($blogIndex as $bItem) {
    $indexBySlug[$bItem['slug']] = $bItem;
}
$relatedPosts = [];
$pushRelated = static function (array $candidate) use (&$relatedPosts, $baseSlug): void {
    $cSlug = (string)($candidate['slug'] ?? '');
    if ($cSlug === '' || $cSlug === $baseSlug || ($candidate['status'] ?? 'published') !== 'published') {
        return;
    }
    foreach ($relatedPosts as $rp) {
        if (($rp['slug'] ?? '') === $cSlug) {
            return;
        }
    }
    $relatedPosts[] = $candidate;
};

foreach (($post['related_slugs'] ?? []) as $rSlug) {
    if (isset($indexBySlug[$rSlug])) {
        $pushRelated($indexBySlug[$rSlug]);
    }
}
$canonicalTreeForRel = zeynart_build_blog_category_tree(zeynart_get_blog_categories($rawStore ?? $store));
if (count($relatedPosts) < 3) {
    foreach ($blogIndex as $bItem) {
        if (count($relatedPosts) >= 3) {
            break;
        }
        $candCats = zeynart_resolve_post_categories($bItem, $canonicalTreeForRel);
        foreach ($candCats as $ccObj) {
            if (isset($postCatIdsSet[(string)$ccObj['id']])) {
                $pushRelated($bItem);
                break;
            }
        }
    }
}
if (count($relatedPosts) < 3) {
    foreach ($blogIndex as $bItem) {
        if (count($relatedPosts) >= 3) {
            break;
        }
        $pushRelated($bItem);
    }
}
$relatedPosts = array_slice($relatedPosts, 0, 3);
if ($curLang !== 'tr' && $relatedPosts !== []) {
    require_once INCLUDES_DIR . '/translator.php';
    $relatedPosts = zeynart_localize_blog_cards($relatedPosts, $curLang, $store);
}

require_once __DIR__ . '/../includes/social_card.php';
require_once __DIR__ . '/../includes/agency_powerhouse.php';
require_once __DIR__ . '/../includes/enterprise_cms_suite.php';
$interlinkCfg = zeynart_get_auto_interlink_config($store);
$showAudioPlayer = !empty($interlinkCfg['audio_player_enabled']);
$authorProfile = zeynart_resolve_post_author($post, $store);
$commentsCfg = zeynart_get_blog_comments_config($store);
$approvedComments = !empty($commentsCfg['enabled']) ? zeynart_get_approved_comments_for_post($baseSlug) : [];

$resolvedCoverImg = trim($coverImg) !== ''
    ? trim($coverImg)
    : (zeynart_extract_first_image($contentHtml) ?: '/images/journal/web-planning.webp');

// 1) Remove duplicate leading <p><img src="..."></p> when its image matches the hero cover image
$coverBase = strtolower(basename((string)parse_url($resolvedCoverImg, PHP_URL_PATH)));
if ($coverBase !== '' && preg_match('~^\s*<p[^>]*>\s*<img\b[^>]*\bsrc=(["\'])([^"\']+)\1[^>]*>\s*</p>\s*~isu', $contentHtml, $leadImgM)) {
    $firstImgBase = strtolower(basename((string)parse_url($leadImgM[2], PHP_URL_PATH)));
    if ($firstImgBase !== '' && $firstImgBase === $coverBase) {
        $contentHtml = (string)substr($contentHtml, strlen($leadImgM[0]));
    }
}

// 2) Extract heading -> following paragraphs map (for repairing truncated auto-FAQs & building Auto-TOC)
$headingAnswerMap = [];
if (preg_match_all('~<h[23][^>]*>(.*?)</h[23]>\s*(<p[^>]*>.*?</p>(?:\s*<p[^>]*>.*?</p>)?)~isu', $contentHtml, $hPairs, PREG_SET_ORDER)) {
    foreach ($hPairs as $hp) {
        $hKey = mb_strtolower(trim(html_entity_decode(strip_tags($hp[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 'UTF-8');
        $hKey = trim(preg_replace('/^\d+[\.\)\-\s]+/u', '', $hKey) ?? $hKey, " .:;?!-\t\n\r\0\x0B");
        $pRaw = preg_replace('~</p>\s*<p[^>]*>~iu', ' ', (string)$hp[2]) ?? (string)$hp[2];
        $pClean = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($pRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        if ($hKey !== '' && $pClean !== '') {
            $headingAnswerMap[$hKey] = $pClean;
        }
    }
}

// 3) Enhance rich HTML prose: promote standalone bold subheadings on legacy h2-less posts, ensure <h2> IDs, add editorial section number badges, build Auto-TOC, style opening paragraph & inline media
$isStructuredSections = ($proseMode === 'sections' || str_contains($contentHtml, 'class="journal-section-number"'));
$renderedHtml = $contentHtml;
$plainForWordCount = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($contentHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
$wordCount = $plainForWordCount !== '' ? max(60, count(preg_split('/\s+/u', $plainForWordCount, -1, PREG_SPLIT_NO_EMPTY) ?: [])) : 120;

if (!$isStructuredSections && trim($renderedHtml) !== '') {
    // 3a) Legacy archive upgrade: if post has zero <h2> headings, promote standalone <p><strong>Subheading</strong></p> (after paragraph 1) into real <h2> sections
    if (!preg_match('~<h2\b~i', $renderedHtml)) {
        $seenAnyTextParas = 0;
        $renderedHtml = preg_replace_callback(
            '~<p\b([^>]*)>(.*?)</p>~isu',
            static function (array $pm) use (&$seenAnyTextParas): string {
                $pAttrs = (string)$pm[1];
                $pInner = (string)$pm[2];
                $plainAll = trim(html_entity_decode(strip_tags($pInner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($plainAll !== '') {
                    $seenAnyTextParas++;
                }
                if ($seenAnyTextParas <= 1 || preg_match('/\bclass=/i', $pAttrs)) {
                    return $pm[0];
                }
                if (!preg_match('~^[\s\x{00A0}]*<(?:strong|b)\b[^>]*>(.*?)</(?:strong|b)>[\s\x{00A0}]*$~isu', $pInner, $bm)) {
                    return $pm[0];
                }
                $innerRaw = (string)$bm[1];
                if (preg_match('~<(?:img|a|br)\b~i', $innerRaw) || preg_match('~</(?:strong|b)>~i', $innerRaw)) {
                    return $pm[0];
                }
                $plainSub = trim(html_entity_decode(strip_tags($innerRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $subLen = mb_strlen($plainSub, 'UTF-8');
                if ($subLen >= 14 && $subLen <= 96 && !str_ends_with($plainSub, '.') && !str_ends_with($plainSub, '!')) {
                    return '<h2>' . trim($innerRaw, " \t\n\r\0\x0B:") . '</h2>';
                }
                return $pm[0];
            },
            $renderedHtml
        ) ?? $renderedHtml;
    }

    $usedIds = [];
    $extractedToc = [];
    $secIndex = 0;

    $renderedHtml = preg_replace_callback(
        '~<h2\b([^>]*)>(.*?)</h2>~isu',
        static function (array $m) use (&$usedIds, &$extractedToc, &$secIndex): string {
            $attrs = (string)$m[1];
            $inner = (string)$m[2];
            $plain = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($plain === '') {
                return $m[0];
            }
            $secIndex++;
            $id = '';
            if (preg_match('/\bid=(["\'])([^"\']+)\1/i', $attrs, $idM)) {
                $id = trim($idM[2]);
            }
            if ($id === '') {
                $baseId = slugify($plain);
                if ($baseId === '') {
                    $baseId = 'bolum-' . $secIndex;
                }
                $id = $baseId;
                $sfx = 2;
                while (isset($usedIds[$id])) {
                    $id = $baseId . '-' . $sfx++;
                }
                $attrs .= ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
            }
            $usedIds[$id] = true;

            if (preg_match('/\bclass=(["\'])([^"\']*)\1/i', $attrs)) {
                $attrs = preg_replace('/\bclass=(["\'])([^"\']*)\1/i', 'class="$2 journal-editorial-h2"', $attrs, 1) ?? $attrs;
            } else {
                $attrs .= ' class="journal-editorial-h2"';
            }

            $extractedToc[] = [
                'id' => $id,
                'title' => preg_replace('/^\d+[\.\)\-\s]+/u', '', $plain) ?: $plain,
            ];

            $alreadyNumbered = (bool)preg_match('/^\s*\d+[\.\)\-]/u', $plain);
            $numStr = str_pad((string)$secIndex, 2, '0', STR_PAD_LEFT);
            if ($alreadyNumbered) {
                return '<h2' . $attrs . '><span class="journal-h2-text">' . $inner . '</span></h2>';
            }
            return '<h2' . $attrs . '><span class="journal-h2-num" aria-hidden="true">' . $numStr . '</span><span class="journal-h2-text">' . $inner . '</span></h2>';
        },
        $renderedHtml
    ) ?? $renderedHtml;

    if ($toc === [] && count($extractedToc) >= 1) {
        $toc = $extractedToc;
    }

    // Wrap standalone inline image paragraphs <p><img ...></p> with journal-inline-media figure class
    $renderedHtml = preg_replace(
        '~<p>(\s*<img\b[^>]*>\s*)</p>~isu',
        '<p class="journal-inline-media">$1</p>',
        $renderedHtml
    ) ?? $renderedHtml;

    // Classify standalone <p><strong>...</strong></p> paragraphs into Lead Standfirst, Key Callout, Sublead, or Studio Sign-off
    if (preg_match_all('~<p\b[^>]*>.*?</p>~isu', $renderedHtml, $allPMatches)) {
        $totalP = count($allPMatches[0]);
        $renderedHtml = preg_replace_callback(
            '~<p\b([^>]*)>(\s*<(?:strong|b)\b[^>]*>(.*?)</(?:strong|b)>\s*)</p>~isu',
            static function (array $pm) use ($totalP): string {
                $pAttrs = (string)$pm[1];
                $strongOuter = (string)$pm[2];
                $strongInner = (string)$pm[3];
                if (preg_match('~</(?:strong|b)>~i', $strongInner)) {
                    return $pm[0];
                }
                $plainText = trim(html_entity_decode(strip_tags($strongInner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $len = mb_strlen($plainText, 'UTF-8');
                if ($len === 0 || preg_match('/\bclass=/i', $pAttrs)) {
                    return $pm[0];
                }
                if (str_ends_with($plainText, ':')) {
                    return '<p' . $pAttrs . ' class="journal-sublead">' . $strongOuter . '</p>';
                }
                if ($len <= 44 && $totalP >= 3) {
                    return '<p' . $pAttrs . ' class="journal-signoff">' . $strongOuter . '</p>';
                }
                if ($len >= 42) {
                    return '<p' . $pAttrs . ' class="journal-key-callout">' . $strongOuter . '</p>';
                }
                return $pm[0];
            },
            $renderedHtml
        ) ?? $renderedHtml;

        // Upgrade very first paragraph: either journal-lead-callout or rich journal-opening-para with anchor id="makale-giris"
        if (preg_match('~^\s*<p([^>]*)\bclass="journal-key-callout"([^>]*)>~isu', $renderedHtml)) {
            $renderedHtml = preg_replace(
                '~^\s*<p([^>]*)\bclass="journal-key-callout"([^>]*)>~isu',
                '<p id="makale-giris"$1class="journal-lead-callout"$2>',
                $renderedHtml,
                1
            ) ?? $renderedHtml;
        } else {
            $renderedHtml = preg_replace_callback(
                '~^(\s*)<p\b([^>]*)>(.*?)</p>~isu',
                static function (array $fp): string {
                    $attrs = (string)$fp[2];
                    $inner = (string)$fp[3];
                    if (preg_match('/\bclass=/i', $attrs) || preg_match('~^\s*<img\b~i', $inner)) {
                        return $fp[0];
                    }
                    $plainFirst = ltrim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $useDropcap = mb_strlen($plainFirst, 'UTF-8') >= 85 && (bool)preg_match('/^\p{L}/u', $plainFirst);
                    $cls = 'journal-opening-para' . ($useDropcap ? ' has-dropcap' : '');
                    $idAttr = preg_match('/\bid=/i', $attrs) ? '' : ' id="makale-giris"';
                    return $fp[1] . '<p' . $idAttr . $attrs . ' class="' . $cls . '">' . $inner . '</p>';
                },
                $renderedHtml,
                1
            ) ?? $renderedHtml;
        }
    }
}

// Extract concise editorial highlights (key sentences containing <strong> emphasis) so short/legacy posts have magazine depth
$editorialHighlights = [];
if (preg_match_all('~<p\b([^>]*)>(.*?)</p>~isu', $renderedHtml, $paraScan, PREG_SET_ORDER)) {
    foreach ($paraScan as $pMatch) {
        if (count($editorialHighlights) >= 3) {
            break;
        }
        $pAttrStr = (string)$pMatch[1];
        $pHtmlItem = (string)$pMatch[2];
        if (str_contains($pAttrStr, 'journal-signoff') || str_contains($pAttrStr, 'journal-inline-media') || !preg_match('~<(?:strong|b)\b~i', $pHtmlItem)) {
            continue;
        }
        $pTxt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($pHtmlItem), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
        if ($pTxt === '' || mb_strlen($pTxt, 'UTF-8') < 48) {
            continue;
        }
        if (preg_match('/^(.{40,260}?[\.!\?])(?:\s|$)/u', $pTxt, $sm)) {
            $candSent = trim($sm[1]);
        } elseif (mb_strlen($pTxt, 'UTF-8') > 230) {
            $cut = mb_substr($pTxt, 0, 225, 'UTF-8');
            $spPos = mb_strrpos($cut, ' ', 0, 'UTF-8');
            $candSent = rtrim($spPos !== false ? mb_substr($cut, 0, $spPos, 'UTF-8') : $cut, ' ,;:-') . '…';
        } else {
            $candSent = $pTxt;
        }
        if ($excerpt !== '' && mb_strtolower(mb_substr($candSent, 0, 45, 'UTF-8'), 'UTF-8') === mb_strtolower(mb_substr(trim($excerpt), 0, 45, 'UTF-8'), 'UTF-8')) {
            continue;
        }
        if (!in_array($candSent, $editorialHighlights, true)) {
            $editorialHighlights[] = $candSent;
        }
    }
}

// Build Right Rail Category Counts & Discovery Feed
$railCatCounts = zeynart_count_posts_per_category($canonicalTreeForRel, $blogIndex, false);
$railCategories = [];
foreach ($treeCategories as $rcCat) {
    $rcId = (string)($rcCat['id'] ?? '');
    $rcTotal = (int)($railCatCounts[$rcId]['total'] ?? 0);
    if ($rcTotal > 0 || (string)($rcCat['slug'] ?? '') === $postCatSlug) {
        $railCategories[] = [
            'name' => (string)$rcCat['name'],
            'slug' => (string)$rcCat['slug'],
            'count' => $rcTotal,
            'active' => ((string)$rcCat['slug'] === $postCatSlug),
        ];
    }
}
usort($railCategories, static fn(array $a, array $b): int => ($b['active'] <=> $a['active']) ?: ($b['count'] <=> $a['count']));
$railCategories = array_slice($railCategories, 0, 8);

$railPosts = $relatedPosts;
if (count($railPosts) < 4) {
    foreach ($blogIndex as $bCand) {
        if (count($railPosts) >= 4) {
            break;
        }
        $cS = (string)($bCand['slug'] ?? '');
        if ($cS === '' || $cS === $baseSlug || ($bCand['status'] ?? 'published') !== 'published') {
            continue;
        }
        $existsInRail = false;
        foreach ($railPosts as $exR) {
            if (($exR['slug'] ?? '') === $cS) {
                $existsInRail = true;
                break;
            }
        }
        if (!$existsInRail) {
            $railPosts[] = $bCand;
        }
    }
}
$postTags = array_values(array_unique(array_filter(array_map(
    static fn($tg): string => trim((string)$tg),
    is_array($post['tags'] ?? null) ? $post['tags'] : []
), static fn(string $tg): bool => $tg !== '' && mb_strtolower($tg, 'UTF-8') !== 'genel')));

// 4) Polish FAQs (fix legacy mid-word "..." truncation & robotic question suffixes cleanly)
$polishedFaqs = [];
foreach ($faqs as $fq) {
    $q = trim((string)($fq['q'] ?? ''));
    $a = trim((string)($fq['a'] ?? ''));
    if ($q === '' || $a === '') {
        continue;
    }
    $lookupStem = $q;
    if (str_ends_with($q, ' konusunda nelere dikkat edilmelidir?')) {
        $stem = trim(substr($q, 0, -strlen(' konusunda nelere dikkat edilmelidir?')));
        $lookupStem = $stem;
        if ($curLang === 'tr' && $stem !== '') {
            if (str_contains($stem, ':')) {
                $q = $stem . ' yaklaşımı neyi ifade eder?';
            } elseif (mb_strlen($stem, 'UTF-8') > 42) {
                $q = $stem . ' neden kritik öneme sahiptir?';
            } else {
                $q = $stem . ' sürecinde nasıl bir yaklaşım izlenir?';
            }
        }
    }
    if (str_ends_with($a, '...')) {
        $normStem = mb_strtolower(trim(preg_replace('/^\d+[\.\)\-\s]+/u', '', rtrim($lookupStem, '.:;?!- ')) ?? $lookupStem), 'UTF-8');
        if ($normStem !== '' && isset($headingAnswerMap[$normStem])) {
            $fullPara = $headingAnswerMap[$normStem];
            if (mb_strlen($fullPara, 'UTF-8') > 360) {
                $slice = mb_substr($fullPara, 0, 360, 'UTF-8');
                $sentEnd = max(
                    (int)mb_strrpos($slice, '.', 0, 'UTF-8'),
                    (int)mb_strrpos($slice, '!', 0, 'UTF-8'),
                    (int)mb_strrpos($slice, '?', 0, 'UTF-8')
                );
                if ($sentEnd >= 110) {
                    $a = mb_substr($slice, 0, $sentEnd + 1, 'UTF-8');
                } else {
                    $sp = mb_strrpos($slice, ' ', 0, 'UTF-8');
                    $a = rtrim($sp !== false ? mb_substr($slice, 0, $sp, 'UTF-8') : $slice, ' ,;:-') . '…';
                }
            } else {
                $a = $fullPara;
            }
        } else {
            $withoutDots = substr($a, 0, -3);
            $sp = mb_strrpos($withoutDots, ' ', 0, 'UTF-8');
            if ($sp !== false && $sp > 60) {
                $a = rtrim(mb_substr($withoutDots, 0, $sp, 'UTF-8'), ' ,;:-') . '…';
            }
        }
    }
    $polishedFaqs[] = ['q' => $q, 'a' => $a];
}
$faqs = $polishedFaqs;

$resolvedBlogDesc = trim((string)($seo['meta_desc'] ?? '')) !== ''
    ? trim((string)$seo['meta_desc'])
    : (trim($excerpt) !== '' ? trim($excerpt) : zeynart_extract_plain_excerpt($contentHtml, 185));
$resolvedSocialCard = zeynart_resolve_social_image(
    trim((string)($seo['og_image'] ?? '')) !== '' ? (string)$seo['og_image'] : $resolvedCoverImg,
    $store
);

$rootOrgDomain = rtrim(canonical_url('/', $store, 'tr'), '/');
$articleCanonical = canonical_url('/blog/' . $slug . '/', $store);
$schemaType = in_array((string)($seo['schema_type'] ?? 'BlogPosting'), ['BlogPosting', 'TechArticle', 'NewsArticle', 'Article'], true)
    ? (string)$seo['schema_type']
    : 'BlogPosting';

$authorSameAs = array_values(array_filter([
    (string)($authorProfile['linkedin'] ?? ''),
    (string)($authorProfile['twitter'] ?? ''),
    (string)($authorProfile['website'] ?? ''),
]));
$authorSchemaNode = [
    '@type' => 'Person',
    '@id' => $rootOrgDomain . '/#author-' . ($authorProfile['slug'] ?? 'editorial'),
    'name' => (string)($authorProfile['name'] ?? ($post['author'] ?? 'Zeynart Web Bilişim')),
    'jobTitle' => (string)($authorProfile['role'] ?? 'Dijital Strateji Uzmanı'),
    'description' => (string)($authorProfile['bio'] ?? ''),
    'image' => canonical_url((string)($authorProfile['avatar'] ?? '/icon.png'), $store, 'tr'),
    'worksFor' => ['@id' => $rootOrgDomain . '/#organization'],
];
if (!empty($authorProfile['expertise'])) {
    $authorSchemaNode['knowsAbout'] = array_values((array)$authorProfile['expertise']);
}
if ($authorSameAs !== []) {
    $authorSchemaNode['sameAs'] = $authorSameAs;
}

$blogSchema = [
    '@type' => $schemaType,
    '@id' => $articleCanonical . '#article',
    'mainEntityOfPage' => ['@id' => $articleCanonical . '#webpage'],
    'headline' => $title,
    'description' => $resolvedBlogDesc,
    'image' => [$resolvedSocialCard['url'], canonical_url($resolvedCoverImg, $store)],
    'datePublished' => $dateIso . 'T09:00:00+03:00',
    'dateModified' => $updatedIso . 'T09:00:00+03:00',
    'articleSection' => $category,
    'inLanguage' => $langMeta['bcp47'],
    'author' => $authorSchemaNode,
    'publisher' => [
        '@id' => $rootOrgDomain . '/#organization',
    ],
];
if (!empty($seo['ai_summary'])) {
    $blogSchema['abstract'] = (string)$seo['ai_summary'];
}

$detailBreadcrumbs = [
    ['name' => t('Ana sayfa'), 'url' => '/'],
    ['name' => t('Blog'), 'url' => '/' . $blogBaseSlug . '/'],
];
foreach (($primaryPostCat['ancestor_breadcrumbs'] ?? []) as $ancCrumb) {
    if (!empty($ancCrumb['slug']) && !empty($ancCrumb['name'])) {
        $ancLoc = $catBySlugMap[(string)$ancCrumb['slug']] ?? null;
        $detailBreadcrumbs[] = [
            'name' => (string)($ancLoc['name'] ?? $ancCrumb['name']),
            'url' => '/' . $blogBaseSlug . '/kategori/' . $ancCrumb['slug'] . '/',
        ];
    }
}
$detailBreadcrumbs[] = [
    'name' => $category,
    'url' => '/' . $blogBaseSlug . '/kategori/' . $postCatSlug . '/',
];
$detailBreadcrumbs[] = [
    'name' => $title,
    'url' => '/' . $blogBaseSlug . '/' . $slug . '/',
];

$pageMeta = [
    'title' => trim((string)($seo['meta_title'] ?? '')) !== '' ? (string)$seo['meta_title'] : ($title . ' | ZEYNART'),
    'description' => $resolvedBlogDesc,
    'og_title' => (string)($seo['og_title'] ?? ''),
    'og_description' => (string)($seo['og_desc'] ?? ''),
    'keywords' => $seo['focus_keyword'] ?? '',
    'robots' => ($seo['robots'] ?? '') ?: 'index, follow',
    'canonical_path' => '/' . $blogBaseSlug . '/' . $slug . '/',
    'canonical_override' => ($curLang === 'tr') ? ($seo['canonical'] ?? '') : '',
    'lang_alternates' => $post['lang_alternates'] ?? [],
    'og_type' => 'article',
    'og_image' => trim((string)($seo['og_image'] ?? '')) !== '' ? (string)$seo['og_image'] : $resolvedCoverImg,
    'preload_image' => $resolvedCoverImg,
    'published_time' => $dateIso . 'T09:00:00+03:00',
    'modified_time' => $updatedIso . 'T09:00:00+03:00',
    'article_section' => $category,
    'ai_summary' => ($seo['ai_summary'] ?? '') ?: $resolvedBlogDesc,
    'faqs' => $faqs,
    'schema_nodes' => [$blogSchema],
    'breadcrumbs' => $detailBreadcrumbs,
];

$showShareBar = ($store['blog_settings']['show_share_bar'] ?? true) !== false;
$shareUrl = canonical_url('/' . $blogBaseSlug . '/' . $slug . '/', $store, $curLang);
$encUrl   = rawurlencode($shareUrl);
$encTitle = rawurlencode($title);
$encWa    = rawurlencode($title . ' — ' . $shareUrl);
$encBody  = rawurlencode($title . "\n\n" . $shareUrl);

$heroLead = trim($excerpt) !== '' ? trim($excerpt) : zeynart_extract_plain_excerpt($contentHtml, 175);
$hasAiTldr = !empty($store['ai_seo']['show_article_tldr']) && !empty($seo['ai_summary']);
$showHighlightsBox = !$hasAiTldr && $editorialHighlights !== [];

$displayToc = $toc;
if ($displayToc === []) {
    if ($showHighlightsBox) {
        $displayToc[] = ['id' => 'one-cikan-notlar', 'title' => t('Öne Çıkan Notlar')];
    }
    $displayToc[] = ['id' => 'makale-giris', 'title' => t('Makale Özeti & Giriş')];
}
if ($faqs !== []) {
    $displayToc[] = ['id' => 'sikca-sorulan-sorular', 'title' => t('Sıkça Sorulan Sorular')];
}
if (!empty($commentsCfg['show_author_box']) && count($displayToc) < 6) {
    $displayToc[] = ['id' => 'journalAuthorCard', 'title' => t('Editoryal Ekip & Yazar')];
}
if (!empty($commentsCfg['enabled']) && count($displayToc) < 6) {
    $displayToc[] = ['id' => 'journalCommentsSection', 'title' => t('Okuyucu Soruları')];
}
if ($relatedPosts !== [] && count($displayToc) < 7) {
    $displayToc[] = ['id' => 'ilgili-yazilar', 'title' => t('Benzer İçerikler')];
}

require INCLUDES_DIR . '/site_header.php';
?>
<article class="journal-article journal-editorial">
  <div class="journal-ambient-glow" aria-hidden="true"></div>

  <div class="journal-shell journal-topbar-shell">
    <div class="journal-topbar-nav">
      <a class="journal-back-pill" href="<?= e(url('/' . $blogBaseSlug . '/')) ?>">
        <?= zeynart_icon('arrow-left', 15) ?>
        <span><?= e(t('ZEYNART JOURNAL’A DÖN')) ?></span>
      </a>
      <nav class="journal-crumbs" aria-label="Breadcrumb">
        <a href="<?= e(url('/')) ?>"><?= e(t('Ana sayfa')) ?></a>
        <span aria-hidden="true">/</span>
        <a href="<?= e(url('/' . $blogBaseSlug . '/')) ?>"><?= e(t('Blog')) ?></a>
        <?php foreach (($primaryPostCat['ancestor_breadcrumbs'] ?? []) as $ancCrumb):
            if (!empty($ancCrumb['slug']) && !empty($ancCrumb['name'])):
                $ancLoc = $catBySlugMap[(string)$ancCrumb['slug']] ?? null;
        ?>
        <span aria-hidden="true">/</span>
        <a href="<?= e(url('/' . $blogBaseSlug . '/kategori/' . $ancCrumb['slug'] . '/')) ?>"><?= e((string)($ancLoc['name'] ?? $ancCrumb['name'])) ?></a>
        <?php endif; endforeach; ?>
        <span aria-hidden="true">/</span>
        <a href="<?= e(url('/' . $blogBaseSlug . '/kategori/' . $postCatSlug . '/')) ?>" class="is-current"><?= e($category) ?></a>
      </nav>
    </div>
  </div>

  <header class="journal-shell journal-article-header journal-hero-deck">
    <div class="journal-hero-copy">
      <div class="journal-meta-pills">
        <?php foreach ($resolvedPostCats as $rcItem): ?>
        <a class="journal-cat-pill" href="<?= e(url('/' . $blogBaseSlug . '/kategori/' . $rcItem['slug'] . '/')) ?>"><i></i><?= e($rcItem['name']) ?></a>
        <?php endforeach; ?>
        <span class="journal-meta-chip">
          <time datetime="<?= e($dateIso) ?>"><?= e($dateDisplay) ?></time>
        </span>
        <span class="journal-meta-chip">
          <?= zeynart_icon('clock-3', 13) ?>
          <span><?= e($readTime) ?> <?= e(t('okuma')) ?></span>
        </span>
        <span class="journal-meta-chip is-words">
          <span><?= number_format($wordCount, 0, ',', '.') ?> <?= e(t('kelime')) ?></span>
        </span>
      </div>

      <h1><?= e($title) ?></h1>

      <?php if ($heroLead !== ''): ?>
      <p class="journal-article-lead"><?= e($heroLead) ?></p>
      <?php endif; ?>

      <div class="journal-author">
        <div class="journal-author-left">
          <div class="journal-author-mark">
            <img alt="<?= e((string)$authorProfile['name']) ?>" loading="eager" width="48" height="48" decoding="async" src="<?= e(url((string)($authorProfile['avatar'] ?? ($store['site']['logo'] ?? '/icon.png')))) ?>"/>
          </div>
          <div class="journal-author-info">
            <strong><?= e((string)($authorProfile['name'] ?? ($post['author'] ?? 'Zeynart Web Bilişim'))) ?></strong>
            <span><?= e((string)($authorProfile['role'] ?? ($post['author_role'] ?? t('Tasarım & Teknoloji Stüdyosu')))) ?></span>
          </div>
        </div>

        <?php if ($showShareBar): ?>
        <div class="journal-quick-share" role="group" aria-label="<?= e(t('BU YAZIYI PAYLAŞIN')) ?>">
          <a class="journal-quick-share-btn" href="https://wa.me/?text=<?= e($encWa) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('WhatsApp ile paylaş')) ?>" title="<?= e(t('WhatsApp ile paylaş')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
          </a>
          <a class="journal-quick-share-btn" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= e($encUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('LinkedIn üzerinde paylaş')) ?>" title="<?= e(t('LinkedIn üzerinde paylaş')) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
          </a>
          <a class="journal-quick-share-btn" href="https://twitter.com/intent/tweet?text=<?= e($encTitle) ?>&amp;url=<?= e($encUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('X üzerinde paylaş')) ?>" title="<?= e(t('X üzerinde paylaş')) ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <button type="button" class="journal-quick-share-btn is-copy-pill" data-share-copy="<?= e($shareUrl) ?>" data-default-label="<?= e(t('Kopyala')) ?>" data-copied-label="<?= e(t('Kopyalandı ✓')) ?>" title="<?= e(t('Bağlantıyı Kopyala')) ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <span data-share-copy-text="1"><?= e(t('Kopyala')) ?></span>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <figure class="journal-article-cover">
      <div class="journal-cover-stage">
        <img alt="<?= e($coverAlt) ?>" fetchpriority="high" width="1672" height="941" decoding="async" src="<?= e(url($resolvedCoverImg)) ?>"/>
      </div>
      <figcaption>
        <span class="journal-cover-brand"><i></i>ZEYNART JOURNAL</span>
        <span class="journal-cover-cat"><?= e($category) ?></span>
      </figcaption>
    </figure>
  </header>

  <div class="journal-shell journal-reading-layout" data-journal-article-body>
    <aside class="journal-toc" aria-label="<?= e(t('Okuma Gezgini')) ?>">
      <div class="journal-toc-card">
        <div class="journal-progress-widget" aria-hidden="true">
          <div class="journal-progress-top">
            <span><?= e(t('OKUMA İLERLEMESİ')) ?></span>
            <strong data-journal-pct>0%</strong>
          </div>
          <div class="journal-progress-track">
            <i data-journal-bar></i>
          </div>
          <div class="journal-progress-sub">
            <span><?= e($readTime) ?> <?= e(t('okuma')) ?></span>
            <span>·</span>
            <span><?= number_format($wordCount, 0, ',', '.') ?> <?= e(t('kelime')) ?></span>
          </div>
        </div>

        <div class="journal-comfort-bar" role="group" aria-label="<?= e(t('Okuma Boyutu & Yazdır')) ?>">
          <span class="journal-comfort-lbl"><?= e(t('YAZI BOYUTU')) ?></span>
          <div class="journal-comfort-btns">
            <button type="button" class="jcb-btn" data-journal-zoom="0.93" title="<?= e(t('Kompakt Yazı Boyutu')) ?>">A-</button>
            <button type="button" class="jcb-btn is-active" data-journal-zoom="1" title="<?= e(t('Standart Yazı Boyutu')) ?>">A</button>
            <button type="button" class="jcb-btn" data-journal-zoom="1.1" title="<?= e(t('Büyük Yazı Boyutu')) ?>">A+</button>
            <button type="button" class="jcb-btn is-print" data-journal-print="1" title="<?= e(t('Yazdır / PDF Kaydet')) ?>" aria-label="<?= e(t('Yazdır / PDF Kaydet')) ?>">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
            </button>
          </div>
        </div>

        <?php if ($displayToc !== []): ?>
        <div class="journal-toc-block">
          <span class="journal-toc-eyebrow"><?= e(t('BU YAZIDA')) ?></span>
          <nav aria-label="<?= e(t('Yazı içindekiler')) ?>" data-journal-toc-nav>
            <?php foreach ($displayToc as $tIdx => $tItem):
                $tNum = str_pad((string)($tIdx + 1), 2, '0', STR_PAD_LEFT);
            ?>
            <a href="#<?= e($tItem['id'] ?? ('bolum-' . ($tIdx + 1))) ?>"<?= $tIdx === 0 ? ' class="is-active"' : '' ?>>
              <span class="journal-toc-num"><?= e($tNum) ?></span>
              <span class="journal-toc-label"><?= e($tItem['title'] ?? '') ?></span>
            </a>
            <?php endforeach; ?>
          </nav>
        </div>
        <?php endif; ?>

        <div class="journal-sidebar-cta">
          <span class="journal-sidebar-kicker"><?= e(t('İLGİLİ HİZMET')) ?></span>
          <a class="journal-service-link" href="<?= e(url((string)($srvLink['href'] ?? '/hizmetler/'))) ?>">
            <span><?= e($srvLink['label'] ?? t('Hizmetlerimiz')) ?></span>
            <?= zeynart_icon('arrow-up-right', 16) ?>
          </a>
          <a class="journal-consult-btn" href="<?= e(url('/iletisim/')) ?>">
            <span><?= e(t('Ücretsiz Ön Görüşme')) ?></span>
            <?= zeynart_icon('arrow-up-right', 15) ?>
          </a>
        </div>
      </div>
    </aside>

    <div class="journal-prose journal-prose-surface" data-journal-prose>
      <div class="journal-surface-ribbon">
        <span class="jsr-badge"><i></i> <?= e(t('ZEYNART EDİTORYAL YAYIN')) ?></span>
        <span class="jsr-meta">✓ <?= e(t('Editör Onaylı İçerik')) ?> · <time datetime="<?= e($updatedIso) ?>"><?= e($dateDisplay) ?></time></span>
      </div>

      <?php if ($showAudioPlayer): ?>
      <div class="journal-audio-player" id="journalAudioPlayer" data-speech-lang="<?= e($langMeta['bcp47'] ?? 'tr-TR') ?>" data-article-title="<?= e($title) ?>" role="region" aria-label="<?= e(t('Makaleyi Sesli Dinle')) ?>">
        <button type="button" class="jap-play-btn" id="japPlayBtn" aria-label="<?= e(t('Makaleyi Sesli Dinle')) ?>">
          <span class="jap-icon-play" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
          </span>
          <span class="jap-icon-pause" aria-hidden="true" hidden>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
          </span>
        </button>

        <div class="jap-body">
          <div class="jap-top">
            <div class="jap-title-row">
              <span class="jap-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
              <strong id="japStateLabel" data-lbl-idle="<?= e(t('Bu Yazıyı Sesli Dinle')) ?>" data-lbl-playing="<?= e(t('Sesli Okunuyor...')) ?>" data-lbl-paused="<?= e(t('Duraklatıldı')) ?>" data-lbl-done="<?= e(t('Okuma Tamamlandı ✓')) ?>"><?= e(t('Bu Yazıyı Sesli Dinle')) ?></strong>
            </div>
            <span class="jap-meta" id="japMetaText"><?= e($readTime) ?> · Podcast Modu</span>
          </div>
          <div class="jap-track" id="japTrack" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <i id="japBarFill"></i>
          </div>
        </div>

        <div class="jap-controls">
          <button type="button" class="jap-speed-btn" id="japSpeedBtn" title="Okuma Hızı">1x</button>
          <button type="button" class="jap-stop-btn" id="japStopBtn" title="<?= e(t('Başa Sar / Durdur')) ?>" aria-label="<?= e(t('Durdur')) ?>" hidden>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
          </button>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($hasAiTldr): ?>
      <div class="journal-ai-tldr" id="one-cikan-notlar" role="region" aria-label="<?= e(t('Hızlı Özet')) ?>">
        <div class="journal-ai-tldr-head"><?= zeynart_icon('sparkles', 15) ?> <?= e(t('KISA ÖZET (AI / HIZLI BAKIŞ)')) ?></div>
        <p><?= e((string)$seo['ai_summary']) ?></p>
      </div>
      <?php elseif ($showHighlightsBox): ?>
      <div class="journal-highlights-box" id="one-cikan-notlar" role="region" aria-label="<?= e(t('Öne Çıkan Notlar')) ?>">
        <div class="jhb-head">
          <?= zeynart_icon('sparkles', 15) ?>
          <span><?= e(t('HIZLI BAKIŞ · ÖNE ÇIKAN NOTLAR')) ?></span>
        </div>
        <ul class="jhb-list">
          <?php foreach ($editorialHighlights as $ehItem): ?>
          <li><?= e($ehItem) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php
        $renderedHtml = zeynart_apply_auto_internal_links($renderedHtml, $store, '/blog/' . $slug . '/', $curLang);
        $renderedHtml = function_exists('zeynart_version_upload_imgs_in_html') ? zeynart_version_upload_imgs_in_html($renderedHtml) : $renderedHtml;
      ?>
      <?php if ($isStructuredSections): ?>
        <?= $renderedHtml ?>
      <?php else: ?>
        <div class="journal-prose-html">
          <?= $renderedHtml ?>
        </div>
      <?php endif; ?>

      <div class="journal-tags-row" role="group" aria-label="<?= e(t('Konu Başlıkları')) ?>">
        <span class="jtr-label"><?= e(t('KONULAR:')) ?></span>
        <?php foreach ($resolvedPostCats as $rcTag): ?>
        <a class="jtr-pill is-cat" href="<?= e(url('/' . $blogBaseSlug . '/kategori/' . $rcTag['slug'] . '/')) ?>">#<?= e($rcTag['name']) ?></a>
        <?php endforeach; ?>
        <?php foreach (array_slice($postTags, 0, 5) as $tgLabel): ?>
        <a class="jtr-pill" href="<?= e(url('/' . $blogBaseSlug . '/?ara=' . rawurlencode($tgLabel))) ?>">#<?= e($tgLabel) ?></a>
        <?php endforeach; ?>
      </div>

      <?php if ($faqs !== []): ?>
      <div class="journal-faq" id="sikca-sorulan-sorular">
        <div class="journal-faq-head">
          <span class="journal-faq-eyebrow"><?= e(t('SIKÇA SORULAN SORULAR')) ?></span>
          <h2><?= e(t('Sıkça sorulan sorular')) ?></h2>
        </div>
        <div class="journal-faq-list">
          <?php foreach ($faqs as $fIdx => $fq): ?>
          <details class="journal-faq-item"<?= $fIdx === 0 ? ' open' : '' ?>>
            <summary>
              <span><?= e($fq['q'] ?? '') ?></span>
              <i aria-hidden="true"></i>
            </summary>
            <div class="journal-faq-ans">
              <p><?= nl2br_e((string)($fq['a'] ?? '')) ?></p>
            </div>
          </details>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($showShareBar): ?>
      <div class="journal-share-bar" role="region" aria-label="<?= e(t('BU YAZIYI PAYLAŞIN')) ?>">
        <div class="journal-share-head">
          <span class="journal-share-eyebrow"><?= e(t('BU YAZIYI PAYLAŞIN')) ?></span>
          <p class="journal-share-sub"><?= e(t('İçeriği faydalı bulduysanız ekibinizle veya sosyal ağlarınızda paylaşabilirsiniz.')) ?></p>
        </div>
        <div class="journal-share-actions">
          <a class="journal-share-btn is-wa" href="https://wa.me/?text=<?= e($encWa) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('WhatsApp ile paylaş')) ?>" title="<?= e(t('WhatsApp ile paylaş')) ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/></svg>
            <span>WhatsApp</span>
          </a>
          <a class="journal-share-btn is-x" href="https://twitter.com/intent/tweet?text=<?= e($encTitle) ?>&amp;url=<?= e($encUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('X üzerinde paylaş')) ?>" title="<?= e(t('X üzerinde paylaş')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            <span>X</span>
          </a>
          <a class="journal-share-btn is-in" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= e($encUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('LinkedIn üzerinde paylaş')) ?>" title="<?= e(t('LinkedIn üzerinde paylaş')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
            <span>LinkedIn</span>
          </a>
          <a class="journal-share-btn is-fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= e($encUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('Facebook üzerinde paylaş')) ?>" title="<?= e(t('Facebook üzerinde paylaş')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            <span>Facebook</span>
          </a>
          <a class="journal-share-btn is-tg" href="https://t.me/share/url?url=<?= e($encUrl) ?>&amp;text=<?= e($encTitle) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(t('Telegram ile paylaş')) ?>" title="<?= e(t('Telegram ile paylaş')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            <span>Telegram</span>
          </a>
          <a class="journal-share-btn is-mail" href="mailto:?subject=<?= e($encTitle) ?>&amp;body=<?= e($encBody) ?>" aria-label="<?= e(t('E-posta ile paylaş')) ?>" title="<?= e(t('E-posta ile paylaş')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <span><?= e(t('E-posta')) ?></span>
          </a>
          <button type="button" class="journal-share-btn is-copy" data-share-copy="<?= e($shareUrl) ?>" data-default-label="<?= e(t('Bağlantıyı Kopyala')) ?>" data-copied-label="<?= e(t('Kopyalandı ✓')) ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            <span data-share-copy-text="1"><?= e(t('Bağlantıyı Kopyala')) ?></span>
          </button>
          <button type="button" class="journal-share-btn is-native" data-share-native="1" data-share-title="<?= e($title) ?>" data-share-url="<?= e($shareUrl) ?>" hidden>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
            <span><?= e(t('Paylaş')) ?></span>
          </button>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($commentsCfg['show_author_box'])): ?>
      <section class="journal-author-card" id="journalAuthorCard" aria-label="<?= e(t('Makale Yazarı')) ?>">
        <div class="jac-avatar">
          <img src="<?= e(url((string)($authorProfile['avatar'] ?? '/icon.png'))) ?>" alt="<?= e((string)$authorProfile['name']) ?>" width="76" height="76" loading="lazy" decoding="async"/>
        </div>
        <div class="jac-body">
          <div class="jac-head">
            <div>
              <span class="jac-badge">✓ <?= e(t('DOĞRULANMIŞ UZMAN YAZAR')) ?></span>
              <h3><?= e((string)$authorProfile['name']) ?></h3>
              <p class="jac-role"><?= e((string)$authorProfile['role']) ?></p>
            </div>
            <?php if (!empty($authorProfile['linkedin']) || !empty($authorProfile['twitter']) || !empty($authorProfile['website'])): ?>
            <div class="jac-socials">
              <?php if (!empty($authorProfile['linkedin'])): ?>
              <a href="<?= e((string)$authorProfile['linkedin']) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">LinkedIn ↗</a>
              <?php endif; ?>
              <?php if (!empty($authorProfile['twitter'])): ?>
              <a href="<?= e((string)$authorProfile['twitter']) ?>" target="_blank" rel="noopener noreferrer" aria-label="X">X ↗</a>
              <?php endif; ?>
              <?php if (!empty($authorProfile['website'])): ?>
              <a href="<?= e((string)$authorProfile['website']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Web">Web ↗</a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php if (!empty($authorProfile['bio'])): ?>
          <p class="jac-bio"><?= e((string)$authorProfile['bio']) ?></p>
          <?php endif; ?>
          <?php if (!empty($authorProfile['expertise'])): ?>
          <div class="jac-tags">
            <?php foreach ((array)$authorProfile['expertise'] as $expTag): ?>
            <span><?= e((string)$expTag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if (!empty($commentsCfg['enabled'])): ?>
      <section class="journal-comments-section" id="journalCommentsSection" aria-label="<?= e((string)$commentsCfg['heading']) ?>">
        <div class="jcs-head">
          <span class="journal-faq-eyebrow"><?= e(t('OKUYUCU SORULARI & UZMAN YANITLARI')) ?></span>
          <h2><?= e((string)$commentsCfg['heading']) ?> <?php if ($approvedComments !== []): ?><small>(<?= count($approvedComments) ?>)</small><?php endif; ?></h2>
          <p><?= e((string)$commentsCfg['sublead']) ?></p>
        </div>

        <?php if ($approvedComments !== []): ?>
        <div class="jcs-list">
          <?php foreach ($approvedComments as $cmt): ?>
          <article class="jcs-item">
            <div class="jcs-item-top">
              <div class="jcs-user">
                <span class="jcs-avatar-initial"><?= e(mb_strtoupper(mb_substr((string)($cmt['name'] ?? 'Z'), 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                <div>
                  <strong><?= e((string)($cmt['name'] ?? 'Ziyaretçi')) ?></strong>
                  <time datetime="<?= e(substr((string)($cmt['created_at'] ?? ''), 0, 10)) ?>"><?= e(substr((string)($cmt['created_at'] ?? ''), 0, 16)) ?></time>
                </div>
              </div>
            </div>
            <p class="jcs-body"><?= nl2br_e((string)($cmt['comment'] ?? '')) ?></p>
            <?php if (!empty($cmt['admin_reply'])): ?>
            <div class="jcs-reply">
              <div class="jcs-reply-head">
                <span class="jcs-reply-badge">✓ <?= e((string)($cmt['replied_by'] ?: 'Zeynart Uzman Yanıtı')) ?></span>
                <?php if (!empty($cmt['replied_at'])): ?>
                <time><?= e(substr((string)$cmt['replied_at'], 0, 16)) ?></time>
                <?php endif; ?>
              </div>
              <p><?= nl2br_e((string)$cmt['admin_reply']) ?></p>
            </div>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form class="jcs-form" id="journalCommentForm" action="<?= e(url('/api/blog-yorum')) ?>" method="post">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"/>
          <input type="hidden" name="post_slug" value="<?= e($baseSlug) ?>"/>
          <input type="hidden" name="post_title" value="<?= e($title) ?>"/>
          <input type="hidden" name="lang" value="<?= e($curLang) ?>"/>
          <div class="hp-field" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;pointer-events:none">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"/></label>
          </div>
          <div class="jcs-form-grid">
            <div class="jcs-field">
              <label for="jcsName"><?= e(t('Adınız Soyadınız')) ?> *</label>
              <input type="text" id="jcsName" name="name" required maxlength="80" placeholder="<?= e(t('Adınız Soyadınız')) ?>"/>
            </div>
            <div class="jcs-field">
              <label for="jcsEmail"><?= e(t('E-Posta Adresiniz (Yayınlanmaz)')) ?> *</label>
              <input type="email" id="jcsEmail" name="email" required maxlength="160" placeholder="ornek@firma.com"/>
            </div>
          </div>
          <div class="jcs-field">
            <label for="jcsComment"><?= e(t('Sorunuz veya Yorumunuz')) ?> *</label>
            <textarea id="jcsComment" name="comment" rows="3" required maxlength="1800" placeholder="<?= e(t('Bu konuyla ilgili teknik sorunuzu veya görüşünüzü yazın...')) ?>"></textarea>
          </div>
          <div class="jcs-actions">
            <button type="submit" class="jcs-submit-btn" id="jcsSubmitBtn">
              <span><?= e(t('Soruyu / Yorumu Gönder')) ?></span>
              <?= zeynart_icon('arrow-up-right', 15) ?>
            </button>
          </div>
          <div class="jcs-feedback" id="jcsFeedback" hidden></div>
        </form>
      </section>
      <?php endif; ?>

      <div class="journal-article-end">
        <div class="journal-article-end-copy">
          <span class="journal-end-eyebrow"><?= e(t('ZEYNART’TAN BİR NOT')) ?></span>
          <p><?= e(t('Her işletmenin ihtiyacı farklı. Bu fikirleri kendi projenize nasıl uyarlayabileceğinizi birlikte değerlendirelim.')) ?></p>
        </div>
        <div class="journal-article-end-actions">
          <a class="journal-end-btn is-primary" href="<?= e(url('/iletisim/')) ?>">
            <span><?= e(t('Projenizi konuşalım')) ?></span>
            <?= zeynart_icon('arrow-up-right', 16) ?>
          </a>
          <a class="journal-end-btn" href="<?= e(url('/site-analiz/')) ?>">
            <span><?= e(t('Ücretsiz Site Analizi')) ?></span>
            <?= zeynart_icon('arrow-up-right', 15) ?>
          </a>
        </div>
      </div>

      <div class="journal-bottom-back">
        <a class="journal-back-pill" href="<?= e(url('/' . $blogBaseSlug . '/')) ?>">
          <?= zeynart_icon('arrow-left', 15) ?>
          <span><?= e(t('Tüm yazılara dön')) ?></span>
        </a>
      </div>
    </div>

    <aside class="journal-right-rail" aria-label="<?= e(t('Keşfet & Araçlar')) ?>">
      <div class="journal-rail-sticky">
        <?php if ($railPosts !== []): ?>
        <div class="jrr-card">
          <div class="jrr-head">
            <span class="jrr-eyebrow"><?= e(t('OKUMAYA DEVAM EDİN')) ?></span>
            <a class="jrr-all" href="<?= e(url('/' . $blogBaseSlug . '/')) ?>"><?= e(t('Tümü')) ?> →</a>
          </div>
          <div class="jrr-post-list">
            <?php foreach (array_slice($railPosts, 0, 4) as $rp): ?>
            <a class="jrr-post-item" href="<?= e(url('/' . $blogBaseSlug . '/' . ($rp['slug'] ?? '') . '/')) ?>">
              <div class="jrr-post-thumb">
                <img src="<?= e(url((string)($rp['image'] ?? '/images/journal/web-planning.webp'))) ?>" alt="" width="112" height="64" loading="lazy" decoding="async"/>
              </div>
              <div class="jrr-post-copy">
                <span class="jrr-post-cat"><?= e($rp['category'] ?? t_category('GENEL')) ?> · <?= e($rp['read'] ?? '3 dk') ?></span>
                <strong><?= e($rp['title'] ?? '') ?></strong>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($railCategories !== []): ?>
        <div class="jrr-card">
          <div class="jrr-head">
            <span class="jrr-eyebrow"><?= e(t('KONU BAŞLIKLARI')) ?></span>
          </div>
          <div class="jrr-cats">
            <?php foreach ($railCategories as $rc): ?>
            <a class="jrr-cat-pill<?= !empty($rc['active']) ? ' is-active' : '' ?>" href="<?= e(url('/' . $blogBaseSlug . '/kategori/' . $rc['slug'] . '/')) ?>">
              <span><?= e($rc['name']) ?></span>
              <small><?= (int)$rc['count'] ?></small>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="jrr-card is-accent">
          <span class="jrr-eyebrow"><?= e(t('DİJİTAL BÜYÜME ARAÇLARI')) ?></span>
          <h3 class="jrr-cta-title"><?= e(t('Sitenizin Hız & SEO Skorunu Ücretsiz Ölçün')) ?></h3>
          <p class="jrr-cta-desc"><?= e(t('Teknik performans, mobil uyumluluk ve yapay zeka görünürlüğünüzü 10 saniyede analiz edin veya projeniz için anlık bütçe çıkarın.')) ?></p>
          <div class="jrr-cta-btns">
            <a class="jrr-btn is-primary" href="<?= e(url('/site-analiz/')) ?>">
              <span><?= e(t('Ücretsiz Site Analizi')) ?></span>
              <?= zeynart_icon('arrow-up-right', 15) ?>
            </a>
            <a class="jrr-btn" href="<?= e(url('/teklif-sihirbazi/')) ?>">
              <span><?= e(t('Maliyet & Teklif Sihirbazı')) ?></span>
              <?= zeynart_icon('arrow-up-right', 15) ?>
            </a>
          </div>
        </div>
      </div>
    </aside>
  </div>
</article>

<?php if ($relatedPosts !== []): ?>
<section class="journal-shell journal-related" id="ilgili-yazilar">
  <div class="studio-section-heading">
    <div>
      <p class="eyebrow"><?= e(t('BİR SONRAKİ FİKİR')) ?></p>
      <h2><?= e(t('Bunlar da ilginizi çekebilir.')) ?></h2>
    </div>
    <a class="text-link" href="<?= e(url('/' . $blogBaseSlug . '/')) ?>"><?= e(t('Tüm yazılar')) ?> <?= zeynart_icon('arrow-up-right', 17) ?></a>
  </div>
  <div class="journal-related-grid is-cols-<?= count($relatedPosts) ?>">
    <?php foreach ($relatedPosts as $rel): ?>
    <a class="journal-card journal-related-card" href="<?= e(url('/' . $blogBaseSlug . '/' . ($rel['slug'] ?? '') . '/')) ?>">
      <div class="journal-card-cover">
        <img alt="" loading="lazy" decoding="async" class="fill-img" src="<?= e(url((string)($rel['image'] ?? '/images/journal/web-planning.webp'))) ?>"/>
        <span><?= e($rel['category'] ?? t_category('GENEL')) ?></span>
        <i><?= zeynart_icon('arrow-up-right', 18) ?></i>
      </div>
      <div class="journal-card-copy">
        <div class="journal-card-meta">
          <time datetime="<?= e($rel['date'] ?? '') ?>"><?= e($rel['date_display'] ?? '') ?></time>
          <span><?= e($rel['read'] ?? '3 dk') ?> <?= e(t('okuma')) ?></span>
        </div>
        <h3><?= e($rel['title'] ?? '') ?></h3>
        <?php if (!empty($rel['excerpt'])): ?>
        <p><?= e((string)$rel['excerpt']) ?></p>
        <?php endif; ?>
        <span class="journal-read"><?= e(t('Okumaya devam et')) ?> <?= zeynart_icon('arrow-up-right', 16) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?= render_builder_sections($store['core_page_blocks']['post:' . $baseSlug] ?? [], $store) ?>
<?= render_global_cta($store) ?>
<?php require INCLUDES_DIR . '/site_footer.php'; ?>
