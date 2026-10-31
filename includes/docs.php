<?php
const LEGAL_PAGES = [
    'privacy.php' => 'Privacy Policy',
    'terms.php' => 'Terms of Service',
    'disclaimer.php' => 'Disclaimer',
];

function doc_open(string $heading, string $lead, array $toc, bool $legal = true): void
{
    echo '<div class="page-head"><div><h1>' . e($heading) . '</h1><p>' . e($lead) . '</p></div>';
    if ($legal) {
        echo '<span class="badge info">Last updated ' . e(LEGAL_UPDATED) . '</span>';
    }
    echo '</div>';
    if ($legal) {
        echo '<div class="doc-notice"><strong>Project draft.</strong> This document describes how ' . e(SHOP_NAME)
            . ' currently works and should be reviewed by the shop owner or a qualified professional before it is relied on as a binding legal document. It is not legal advice and does not certify compliance with any law.</div>';
    }
    echo '<div class="doc-layout"><aside class="doc-toc card"><h2>On this page</h2><ol>';
    foreach ($toc as $id => $label) {
        echo '<li><a href="#' . e($id) . '">' . e($label) . '</a></li>';
    }
    echo '</ol></aside><article class="doc-body card' . ($legal ? ' numbered' : '') . '">';
}

function doc_close(bool $legal = true): void
{
    echo '</article></div>';
    if ($legal) {
        echo '<nav class="doc-related" aria-label="Related policies"><span>Related policies</span>';
        foreach (LEGAL_PAGES as $file => $label) {
            echo '<a class="btn btn-ghost btn-sm" href="' . e(url($file)) . '">' . e($label) . '</a>';
        }
        echo '<a class="btn btn-ghost btn-sm" href="' . e(url('contact.php')) . '">Contact us</a></nav>';
    }
}
