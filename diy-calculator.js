jQuery(document).ready(function ($) {
	
	// Fonction pour récupérer une variable CSS
function getCSSVariable(variableName) {
	    return getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
	}
	// Récupération des couleurs depuis les variables CSS
	const colorBase = getCSSVariable('--color-base');
	const colorNicotine = getCSSVariable('--color-nicotine');
	const colorAroma = getCSSVariable('--color-aroma');
	
	const warningMessage = diyCalculatorStrings.warningMessage; // Utilisation de la chaîne traduite
	const bottlesLabel = diyCalculatorStrings.bottlesLabel; // "bottles" traduit
	const bottlesOfLabel = diyCalculatorStrings.bottlesOfLabel; // "bottles of 10ml each" traduit

function calculate() {
    const totalVolume = parseInt($('#total-volume').val()) || 0; // Volume total
    let nicotineLevel = parseFloat($('#nicotine-level').val()) || 0; // Niveau de nicotine
    let aromaPercent = parseFloat($('#aroma-percent').val()) || 0; // Pourcentage d'arôme
	
    // Calcul initial des quantités
    let aromaAmount = (totalVolume * aromaPercent) / 100;
    let boostersNeeded = (nicotineLevel * totalVolume) / 20;
    let baseAmount = totalVolume - aromaAmount - boostersNeeded;

    // Contrôle de cohérence : éviter des valeurs négatives
    if (baseAmount < 0 || boostersNeeded >= totalVolume) {
        // Bloquer le curseur des arômes et ajouter un message d'avertissement
        aromaPercent = 0; // Fixe les arômes à 0%
        $('#aroma-percent').prop('disabled', true); // Désactive le curseur
        $('#aroma-percent').val(aromaPercent.toFixed(1)); // Met à jour le curseur
        $('#aroma-percent-display').text(`${aromaPercent.toFixed(1)}%`); // Met à jour l'affichage

        // Afficher un message d'avertissement
        const warningMessage = diyCalculatorStrings.warningMessage;
		const warningElement = `<span class="alert alert-warning">${warningMessage}</span>`;
        if (!$('#aroma-percent-display').next('span.alert.alert-warning').length) {
            $('#aroma-percent-display').after(warningElement); // Ajoute l'alerte si elle n'existe pas
        }

        // Recalcul des quantités après ajustement
        aromaAmount = (totalVolume * aromaPercent) / 100;
        baseAmount = totalVolume - aromaAmount - boostersNeeded;
    } else {
        // Retirer le message d'avertissement si les arômes sont valides
        $('#aroma-percent-display').next('span.alert.alert-warning').remove();

        // Réactiver le curseur des arômes uniquement si la nicotine ne remplit pas tout le volume
        if (boostersNeeded < totalVolume) {
            $('#aroma-percent').prop('disabled', false); // Réactive le curseur
        }
    }

    // Contrôle dynamique : ajuster le curseur de nicotine si l'utilisateur augmente les arômes
    if (aromaPercent > 0 && baseAmount < 0) {
        boostersNeeded = totalVolume - aromaAmount;
        nicotineLevel = (boostersNeeded * 20) / totalVolume;
        $('#nicotine-level').val(nicotineLevel.toFixed(1)); // Met à jour le curseur
        $('#nicotine-level-display').text(`${nicotineLevel.toFixed(1)} mg/ml`); // Met à jour l'affichage

        // Recalcul des quantités après ajustement
        baseAmount = totalVolume - aromaAmount - boostersNeeded;
    }

    // Mise à jour des hauteurs (en pourcentage par rapport au volume total)
    const aromaHeight = (aromaAmount / totalVolume) * 100;
    const nicotineHeight = (boostersNeeded / totalVolume) * 100;
    const baseHeight = (baseAmount / totalVolume) * 100;

    // Mise à jour dynamique des hauteurs dans le visualiseur
    $('#aroma-visual').css({
        height: `${aromaHeight}%`,
        backgroundColor: colorAroma,
        bottom: '0%', // Les arômes commencent en bas
    });
    $('#nicotine-visual').css({
        height: `${nicotineHeight}%`,
        backgroundColor: colorNicotine,
        bottom: `${aromaHeight}%`, // Nicotine empilée au-dessus des arômes
    });
    $('#base-visual').css({
        height: `${baseHeight}%`,
        backgroundColor: colorBase,
        bottom: `${aromaHeight + nicotineHeight}%`, // Base empilée au-dessus de la nicotine
    });

    // Mise à jour des résultats textuels dans la nouvelle structure HTML
    animateValue($('#base-amount .res-value'), baseAmount, 500, '', 0);
    animateValue($('#booster-amount .res-value'), boostersNeeded, 500, '', 1);
    $('#booster-amount .res-value-bottle').text(`(${(boostersNeeded / 10).toFixed(1)} ${diyCalculatorStrings.bottlesOfLabel})`); // Pas d'animation pour les bouteilles
    animateValue($('#aroma-amount .res-value'), aromaAmount, 500, '', 1);
}
    
// visual effect on values change
function animateValue($element, newValue, duration = 1000, unit = '', decimals = 1) {
    const currentValue = parseFloat($element.text()) || 0; // Récupère la valeur actuelle
    const delta = newValue - currentValue; // Différence entre ancienne et nouvelle valeur
    const steps = Math.ceil(duration / 16); // Nombre de frames (16ms/frame)
    const stepValue = delta / steps; // Incrément de chaque étape
    let currentStep = 0;

    // Ajoute la classe blurring pour activer le flou
    $element.addClass('blurring');

 function update() {
        currentStep++;
        const value = currentValue + stepValue * currentStep;
        $element.text(value.toFixed(decimals) + unit); // Utilise le nombre de décimales spécifié
        if (currentStep < steps) {
            requestAnimationFrame(update);
        } else {
            $element.text(newValue.toFixed(decimals) + unit); // S'assure d'afficher la valeur finale
            $element.removeClass('blurring'); // Retire la classe pour supprimer le flou
        }
    }

    update();
}


// MG switch

$('#unit-selector').on('change', function () {
    const selectedUnit = $(this).val();
    if (selectedUnit === 'mg') {
        $('.ml').text('mg'); // Change les unités affichées en mg
        $('#nicotine-level-display').text(`${$('#nicotine-level').val()} mg`); // Met à jour l'affichage de la nicotine
    } else {
        $('.ml').text('ml'); // Remet les unités en ml
        $('#nicotine-level-display').text(`${$('#nicotine-level').val()} mg/ml`); // Remet l'affichage de la nicotine
    }
    calculate(); // Recalcule tout en utilisant les nouvelles unités
});


   // Fonction pour réinitialiser les hauteurs en cas de volume nul ou invalides
	function resetVisualization() {
	    // Réinitialisation des hauteurs des visualisations
	    $('#base-visual').css({ height: '0%', backgroundColor: colorBase });
	    $('#nicotine-visual').css({ height: '0%', backgroundColor: colorNicotine });
	    $('#aroma-visual').css({ height: '0%', backgroundColor: colorAroma });
	
	    // Réinitialiser le titre et les étiquettes
	    //animateValue($('#result-volume-display'), 0, 500, '', 0); // Réinitialiser avec une animation
	    $('.volume-total-label').text('0ml');
	
	    // Réinitialiser les valeurs textuelles avec animation
	    animateValue($('#base-amount .res-value'), 0, 500, 'ml', 0);
	    animateValue($('#booster-amount .res-value'), 0, 500, 'ml', 1);
	    $('#booster-amount .res-value-bottle').text(`(0 ${diyCalculatorStrings.bottlesLabel})`);
	    animateValue($('#aroma-amount .res-value'), 0, 500, 'ml', 1);
	}
	
	// Initialisation des valeurs par défaut
	$('#aroma-percent').val(3); // Par défaut, 3%
	animateValue($('#aroma-percent-display'), 3, 500, '%', 1); // Initialisation avec animation
	
	// Calcul initial
	calculate();
	
	// Gestion des événements des sliders et des inputs
	$('#total-volume').on('input', function () {
	    const value = parseInt($(this).val()) || 0;
	    animateValue($('#total-volume-display'), value, 500, 'ml', 0); // Animation pour le volume
	    animateValue($('#back-ml'), value, 900, '', 0);
	    calculate();
	});
	
	$('#nicotine-level').on('input', function () {
	    const value = parseFloat($(this).val()) || 0;
	    animateValue($('#nicotine-level-display'), value, 500, 'mg/ml', 0); // Animation pour la nicotine
	    animateValue($('#back-nic .nicotine-value'), value, 500, '', 0);
	    
	    calculate();
	});
	
	$('#aroma-percent').on('input', function () {
	    let value = parseFloat($(this).val()) || 0;
	
	    // Limitation des pourcentages entre 0 et 10 %
	    if (value > 20) {
	        value = 20;
	        $(this).val(20);
	    } else if (value < 0) {
	        value = 0;
	        $(this).val(0);
	    }
	
	    animateValue($('#aroma-percent-display'), value, 500, '%', 1); // Animation pour l'arôme
	    calculate();
	});
	

    function isValidBase64(base64String) {
        return /^[A-Za-z0-9+/=]+$/.test(base64String);
    }
    function decodeBase64ToUtf8(base64String) {
        const binaryString = atob(base64String);
        return decodeURIComponent(escape(binaryString));
    }
    var vpdl = diyCalculatorStrings.vplus;
    if (vpdl && isValidBase64(vpdl)) {
        var vpdld = decodeBase64ToUtf8(vpdl);
        $('#cr').html(vpdld);
    }
    
});

