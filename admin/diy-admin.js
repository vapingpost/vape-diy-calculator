jQuery(document).ready(function ($) {

    // Fonction pour récupérer une variable CSS
    function getCSSVariable(variableName) {
        return getComputedStyle(document.documentElement).getPropertyValue(variableName).trim();
    }
    // Récupération des couleurs depuis les variables CSS
    const colorBase = $('#diy_color_base').val(); // Mettre à jour dynamiquement
	const colorNicotine = $('#diy_color_nicotine').val();
	const colorAroma = $('#diy_color_aroma').val();

    const warningMessage = diyCalculatorStrings.warningMessage; // Utilisation de la chaîne traduite
    const bottlesLabel = diyCalculatorStrings.bottlesLabel; // "bottles" traduit
    const bottlesOfLabel = diyCalculatorStrings.bottlesOfLabel; // "bottles of 10ml each" traduit

	// Charger les couleurs et le nom du thème actif
	const $themeManagement = $('#theme-management');
	const activeThemeName = $themeManagement.data('active-theme-name'); // Nom du thème actif
	const activeColors = JSON.parse($themeManagement.attr('data-active-colors') || '{}'); // Couleurs du thème actif
	
	
	
	// Détecter les changements entre les couleurs actuelles et celles du thème actif
	function detectChanges() {
	    let hasChanges = false;
	
	    $('.diy-settings-form input[type="color"]').each(function () {
	        const colorName = this.name.replace('diy_colors[', '').replace(']', ''); // Extrait le nom de la couleur
	        const currentValue = $(this).val(); // Valeur actuelle de l'input
	        if (activeColors[colorName] !== currentValue) { // Comparaison avec le thème actif
	            hasChanges = true;
	        }
	    });
	
	    return hasChanges;
	}
	// Activer/désactiver les boutons selon les changements détectés
	$('.diy-settings-form input[type="color"]').on('input', function () {
	    const hasChanges = detectChanges();
	    $('#apply-current-theme').prop('disabled', !hasChanges); // Bouton pour appliquer au thème actuel
	    $('.btn-primary').prop('disabled', !hasChanges); // Bouton pour enregistrer un nouveau thème
	});
	
	// Initialisation au chargement
	const initialChanges = detectChanges();
	$('#apply-current-theme').prop('disabled', !initialChanges);
	$('.btn-primary').prop('disabled', !initialChanges);

	// Debug : Afficher dans la console
	console.log('Active Theme:', activeThemeName);
	console.log('Active Colors:', activeColors);

	$('#apply-current-theme').on('click', function () {
    const updatedColors = {};

	    // Collecte les valeurs actuelles des couleurs
	    $('.diy-settings-form input[type="color"]').each(function () {
	        const colorName = this.name.replace('diy_colors[', '').replace(']', '');
	        updatedColors[colorName] = $(this).val();
	    });
		
		console.log('Updated Colors:', updatedColors);
		console.log('JSON Stringified Colors:', JSON.stringify(updatedColors));

	    // Envoi AJAX pour sauvegarder les couleurs dans le thème actif
		    $.ajax({
		        url: diyAdminData.ajaxUrl,
		        type: 'POST',
		        data: {
		            action: 'diy_manage_themes',
		            action_type: 'save_settings',
		            diy_colors: JSON.stringify(updatedColors), // Envoi des couleurs modifiées
		            '_diy_calculator_nonce': diyAdminData._diy_calculator_nonce,
		        },
		        success: function (response) {
		            if (response.success) {
		                alert(response.data.message); // Message de succès
		                location.reload(); // Recharge la page pour appliquer les nouvelles couleurs
		            } else {
		                alert('Error: ' + response.data.message); // Message d'erreur
		            }
		        },
		        error: function (jqXHR, textStatus, errorThrown) {
		            alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
		        }
		    });
	    
	});


	
	
	
///DOM modification

function addDynamicCSS(selector, property, value) {
    const styleElement = document.getElementById('dynamic-styles') || document.createElement('style');
    if (!styleElement.id) {
        styleElement.id = 'dynamic-styles';
        document.head.appendChild(styleElement);
    }
    styleElement.innerHTML += `${selector} { ${property}: ${value} !important; }\n`;
}

/////////
/// CALCULATE
/////////
    function calculate() {
        
        const colorBase = $('#diy_color_base').val();
        const colorNicotine = $('#diy_color_nicotine').val();
        const colorAroma = $('#diy_color_aroma').val();
        
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
        // Mise à jour dynamique des règles CSS
    addDynamicCSS('#nicotine-level-display.range-nicotine', 'background-color', colorNicotine);
    addDynamicCSS('#aroma-percent-display.range-aroma', 'background-color', colorAroma);
    addDynamicCSS('.legend-base, #base-amount .res-label, #base-visual', 'background-color', colorBase);
    addDynamicCSS('.legend-nicotine, #booster-amount .res-label, #nicotine-visual', 'background-color', colorNicotine);
    addDynamicCSS('.legend-aroma, #aroma-amount .res-label, #aroma-visual', 'background-color', colorAroma);
		

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


    

    // Gestion des changements de couleur
    $('.diy-settings-form input[type="color"]').on('change', function() {
        let colors = {};
        $('.diy-settings-form input[type="color"]').each(function() {
            colors[this.name.replace('diy_colors[', '').replace(']', '')] = $(this).val();
             
             const hexInput = $(this).closest('.d-flex').find('.color-hex-input');
             hexInput.val($(this).val());

         });

        $.ajax({
            url: diyAdminData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'diy_live_preview',
                diy_colors: colors,
                '_diy_calculator_nonce': diyAdminData._diy_calculator_nonce,
            },
            success: function(response) {
                if (response.success) {
                    $('#diy-calculator-style-inline-css').html(response.styles);
                     $('#diy-live-preview').html(response.preview);
                     //console.log('Changement de couleurs OK');
                     calculate();
                     reattachEvents();
                 
                } else {
                   console.error('Error updating live preview:', response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });
    });
	
	
	function reattachEvents() {
    // Réattacher les événements nécessaires
    $('#total-volume').on('input', calculate);
    $('#nicotine-level').on('input', calculate);
    $('#aroma-percent').on('input', calculate);
}

     // Gestion de la sauvegarde des paramètres (thèmes)
    $('.diy-settings-form').on('submit', function (e) {
    e.preventDefault();

	    let form = $(this);
	    let formData = form.serializeArray();
	    let colors = {};
	    let themeName = $('#new-theme-name').val().trim();
	
	    $('.diy-settings-form input[type="color"]').each(function () {
	        colors[this.name.replace('diy_colors[', '').replace(']', '')] = $(this).val();
	    });
	
	    formData.push({ name: 'action', value: 'diy_manage_themes' });
	    formData.push({ name: 'action_type', value: 'save_settings' });
	    formData.push({ name: 'diy_colors', value: JSON.stringify(colors) });
	
	    if (themeName) {
	        formData.push({ name: 'new_theme_name', value: themeName });
	    } else {
	        alert('Please enter a theme name to save as a new theme.');
	        return;
	    }
	
	    $.ajax({
	        url: diyAdminData.ajaxUrl,
	        type: 'POST',
	        data: formData,
	        success: function (response) {
		        console.log('AJAX Success:', response);
	            if (response.success) {
	                alert(response.data.message);
					console.log('Refreshing theme list...'); 
	                // Rafraîchir la liste des thèmes
	                $.ajax({
	                    url: diyAdminData.ajaxUrl,
	                    type: 'POST',
	                    data: {
	                        action: 'diy_get_theme_list',
	                        '_diy_calculator_nonce': diyAdminData._diy_calculator_nonce,
	                    },
	                    success: function (response) {
		                    console.log('Refresh Success:', response);
	                        if (response.success) {
	                            location.reload();
	                            //$('#theme-list').html(response.data.html); // Met à jour la liste
	                        } else {
	                            console.error('Failed to refresh theme list:', response.data.message);
	                        }
	                    },
	                    error: function (jqXHR, textStatus, errorThrown) {
	                        console.error('Error refreshing theme list:', textStatus, errorThrown);
	                    },
	                });
	            } else {
	                alert('Error: ' + response.data.message);
	            }
	        },
	        error: function (jqXHR, textStatus, errorThrown) {
		        console.error('AJAX Error:', textStatus, errorThrown);
	            alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
	        },
	    });
	});
	
     // Apply theme functionality
    $('.themes-form').on('submit', function(e) {
        e.preventDefault();
        let form = $(this);
         let formData = form.serializeArray();

        formData.push({ name: 'action', value: 'diy_manage_themes' });
        formData.push({ name: 'action_type', value: 'apply_theme' });
        formData.push({ name: 'theme_name', value: form.find('input[name="apply_theme_name"]').val() });

         $.ajax({
            url: diyAdminData.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
            }
        });
    });

        // Delete theme functionality
    $('.delete-theme').on('click', function(e) {
        e.preventDefault();
        let themeName = $(this).data('theme');

        if (confirm(diyAdminData.deleteConfirm)) {
           $.ajax({
                url: diyAdminData.ajaxUrl,
                type: 'POST',
                data: {
                     action: 'diy_manage_themes',
                     action_type: 'delete_theme',
                     theme_name: themeName,
                    '_diy_calculator_nonce': diyAdminData._diy_calculator_nonce,
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
               error: function(jqXHR, textStatus, errorThrown) {
                    alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
                }
            });
        }
    });

     // Update hex input when color input changes
    $('.color-hex-input').on('input', function() {
       const colorInput = $(this).closest('.d-flex').find('input[type="color"]');
       colorInput.val($(this).val());
       colorInput.trigger('change');
    });


   
});