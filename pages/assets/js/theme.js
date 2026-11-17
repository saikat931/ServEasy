/**
 * ServEasy - Multi-Theme Controller
 * Seamlessly manages Cosmic (Default), Pitch Black, and Clean White themes.
 */

(function (window, document) {
  'use strict';

  function applyTheme(theme) {
    if (!['default', 'black', 'white'].includes(theme)) {
      theme = 'default';
    }
    try {
      localStorage.setItem('serveasy_theme', theme);
    } catch (e) {}

    document.documentElement.setAttribute('data-theme', theme);
    if (document.body) {
      document.body.classList.remove('theme-default', 'theme-black', 'theme-white');
      document.body.classList.add('theme-' + theme);
    }

    // Update header toggle icon
    const icon = document.getElementById('theme-toggle-icon');
    if (icon) {
      if (theme === 'black') {
        icon.setAttribute('name', 'moon-outline');
      } else if (theme === 'white') {
        icon.setAttribute('name', 'sunny-outline');
      } else {
        icon.setAttribute('name', 'color-palette-outline');
      }
    }

    // Update active state in dropdown
    document.querySelectorAll('.theme-opt-btn').forEach(btn => {
      if (btn.getAttribute('data-theme') === theme) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }

  function init() {
    let saved = 'default';
    try {
      saved = localStorage.getItem('serveasy_theme') || 'default';
    } catch (e) {}
    applyTheme(saved);

    const toggleBtn = document.getElementById('theme-toggle-btn');
    const dropdown = document.getElementById('theme-dropdown-menu');

    if (toggleBtn && dropdown) {
      toggleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('active');
      });

      document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== toggleBtn) {
          dropdown.classList.remove('active');
        }
      });

      document.querySelectorAll('.theme-opt-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          const targetTheme = this.getAttribute('data-theme');
          applyTheme(targetTheme);
          dropdown.classList.remove('active');
        });
      });
    }
  }

  // Expose global controller
  window.setServEasyTheme = applyTheme;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
