/* Fenêtre d'activation des statistiques (écrans Chic-checkout). */
(function () {
	var modal = document.getElementById('chic-onboarding');
	if (!modal) { return; }
	modal.querySelector('.button-primary').focus();
	// Échap : ferme pour cette page seulement (la proposition reviendra).
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { modal.remove(); } });
})();
