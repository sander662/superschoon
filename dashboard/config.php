<?php
/**
 * Super Schoon – dashboard-instellingen.
 * Het beginwachtwoord staat hier alleen als hash (bcrypt). Wijzig het na de eerste login
 * via het dashboard: het nieuwe wachtwoord wordt op de server bewaard (data/wachtwoord.hash)
 * en gaat voor op deze hash.
 */
if (!defined('SUPERSCHOON')) { http_response_code(404); exit; }
const DASH_WACHTWOORD_HASH = '$2y$10$/JOk9/D8H0wK8/K8dEH/J.Cxkd.8UeBFvOdgCyxz7jIVoefCWCoG6';
