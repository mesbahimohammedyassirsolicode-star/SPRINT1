<?php
/**
 * Shared HTML Head with TailWind Config, Google Fonts & Material Symbols
 * Matching Stitch Mockup Design Tokens
 */
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $pageTitle ?? 'LuxeStay Grand Riviera & Spa - Executive Front Desk' ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS with custom plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "inverse-primary": "#735c00",
              "on-primary-fixed": "#241a00",
              "on-tertiary": "#003824",
              "surface-bright": "#31394d",
              "surface": "#0b1326",
              "outline": "#99907c",
              "on-surface-variant": "#d0c5af",
              "background": "#0b1326",
              "error-container": "#93000a",
              "surface-container-lowest": "#060e20",
              "surface-container-highest": "#2d3449",
              "outline-variant": "#4d4635",
              "on-secondary-fixed": "#261900",
              "tertiary-container": "#33ca90",
              "on-error": "#690005",
              "secondary-container": "#604403",
              "on-secondary": "#412d00",
              "on-primary": "#3c2f00",
              "surface-container-low": "#131b2e",
              "primary-fixed": "#ffe088",
              "secondary-fixed": "#ffdea5",
              "on-tertiary-fixed": "#002113",
              "on-tertiary-container": "#005035",
              "secondary-fixed-dim": "#e9c176",
              "on-error-container": "#ffdad6",
              "tertiary-fixed-dim": "#4edea3",
              "on-background": "#dae2fd",
              "inverse-on-surface": "#283044",
              "on-surface": "#dae2fd",
              "on-primary-container": "#554300",
              "on-secondary-container": "#dab36a",
              "error": "#ffb4ab",
              "primary-fixed-dim": "#e9c349",
              "surface-variant": "#2d3449",
              "tertiary-fixed": "#6ffbbe",
              "surface-tint": "#e9c349",
              "on-secondary-fixed-variant": "#5d4201",
              "primary-container": "#d4af37",
              "surface-container-high": "#222a3d",
              "on-tertiary-fixed-variant": "#005236",
              "primary": "#f2ca50",
              "surface-dim": "#0b1326",
              "secondary": "#e9c176",
              "on-primary-fixed-variant": "#574500",
              "inverse-surface": "#dae2fd",
              "surface-container": "#171f33",
              "tertiary": "#58e7aa"
            },
            fontFamily: {
              serif: ["Playfair Display", "serif"],
              sans: ["Plus Jakarta Sans", "sans-serif"]
            }
          }
        }
      }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 350, 'GRAD' 0, 'opsz' 20;
            font-size: 19px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #060e20;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #2d3449;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #4d4635;
        }
        .champagne-glow {
            box-shadow: 0 0 16px rgba(212, 175, 55, 0.22);
        }

        /* ---- Accessibility: visible keyboard focus ---- */
        :focus-visible {
            outline: 2px solid rgba(212, 175, 55, 0.85);
            outline-offset: 2px;
            border-radius: 4px;
        }
        @supports not selector(:focus-visible) {
            :focus {
                outline: 2px solid rgba(212, 175, 55, 0.85);
                outline-offset: 2px;
            }
        }

        /* ---- Legibility floor: never render micro-labels below 10px ---- */
        .text-\[9px\] {
            font-size: 10px !important;
        }

        /* ---- Tablet / mobile: sidebar collapses to an icon rail (80px) ---- */
        @media (max-width: 1023px) {
            aside[class*="w-64"] {
                width: 5rem;
            }
            aside[class*="w-64"] a[class*="gap-3"] span:last-child,
            aside[class*="w-64"] a[class*="gap-3"] div:last-child,
            aside[class*="w-64"] a[class*="w-full"] span:last-child,
            aside[class*="w-64"] [class*="flex-col min-w-0"] {
                display: none;
            }
            aside[class*="w-64"] nav a,
            aside[class*="w-64"] a[class*="w-full"] {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            .pl-64 {
                padding-left: 5rem !important;
            }
            .left-64 {
                left: 5rem !important;
            }
        }
    </style>
</head>
<body class="bg-background text-on-surface font-sans antialiased min-h-screen selection:bg-primary-container selection:text-on-primary-fixed overflow-x-hidden">
