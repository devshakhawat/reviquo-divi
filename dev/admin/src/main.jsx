import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import ShortcodeApp from './ShortcodeApp';
import './styles/globals.css';

const root = document.getElementById('sktpr-admin-root');
const shortcodeRoot = document.getElementById('sktpr-shortcode-root');

if (root) {
  createRoot(root).render(<App />);
}

if (shortcodeRoot) {
  createRoot(shortcodeRoot).render(<ShortcodeApp />);
}
