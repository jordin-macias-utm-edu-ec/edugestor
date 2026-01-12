<?php
// includes/functions.php

function getCategoryStyle($categoria) {
    switch ($categoria) {
        case 'Tecnología':
            return ['icon' => 'fa-laptop', 'color' => 'text-primary', 'bg' => 'bg-primary-subtle'];
        case 'Laboratorio':
            return ['icon' => 'fa-flask', 'color' => 'text-success', 'bg' => 'bg-success-subtle'];
        case 'Espacios':
            return ['icon' => 'fa-building', 'color' => 'text-info', 'bg' => 'bg-info-subtle'];
        default:
            return ['icon' => 'fa-box', 'color' => 'text-secondary', 'bg' => 'bg-light'];
    }
}

// ... aquí tus otras funciones como authenticate(), redirectIfLoggedIn(), etc.