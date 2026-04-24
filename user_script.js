document.addEventListener("DOMContentLoaded", function() {
    // Side Panel Control for Ladies Menu
    const ladiesMenuItem = document.getElementById('ladiesMenuItem');
    const ladiesSidePanel = document.getElementById('ladiesSidePanel');
    const panelOverlay = document.getElementById('panelOverlay');
    const closeLadiesPanelBtn = document.getElementById('closeLadiesPanelBtn');

    // Side Panel Control for Men Menu
    const menMenuItem = document.getElementById('menMenuItem');
    const menSidePanel = document.getElementById('menSidePanel');
    const closeMenPanelBtn = document.getElementById('closeMenPanelBtn');

    // Side Panel Control for Kids Menu
    const kidsMenuItem = document.getElementById('kidsMenuItem');
    const kidsSidePanel = document.getElementById('kidsSidePanel');
    const closeKidsPanelBtn = document.getElementById('closeKidsPanelBtn');

    // Function to close all panels
    function closeAllPanels() {
        if(ladiesSidePanel) ladiesSidePanel.classList.remove('active');
        if(menSidePanel) menSidePanel.classList.remove('active');
        if(kidsSidePanel) kidsSidePanel.classList.remove('active');
        if(panelOverlay) panelOverlay.classList.remove('active');
    }

    if(ladiesMenuItem && ladiesSidePanel && panelOverlay) {
        ladiesMenuItem.addEventListener('mouseenter', function() {
            closeAllPanels();
            ladiesSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });
        ladiesSidePanel.addEventListener('mouseenter', function() {
            ladiesSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });
        ladiesMenuItem.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!ladiesSidePanel.matches(':hover')) {
                    ladiesSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });
        ladiesSidePanel.addEventListener('mouseleave', function() {
            ladiesSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });
        if(closeLadiesPanelBtn) {
            closeLadiesPanelBtn.addEventListener('click', function() {
                closeAllPanels();
            });
        }
    }

    if(menMenuItem && menSidePanel && panelOverlay) {
        menMenuItem.addEventListener('mouseenter', function() {
            closeAllPanels();
            menSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });
        menSidePanel.addEventListener('mouseenter', function() {
            menSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });
        menMenuItem.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!menSidePanel.matches(':hover')) {
                    menSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });
        menSidePanel.addEventListener('mouseleave', function() {
            menSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });
        if(closeMenPanelBtn) {
            closeMenPanelBtn.addEventListener('click', function() {
                closeAllPanels();
            });
        }
    }

    if(kidsMenuItem && kidsSidePanel && panelOverlay) {
        kidsMenuItem.addEventListener('mouseenter', function() {
            closeAllPanels();
            kidsSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });
        kidsSidePanel.addEventListener('mouseenter', function() {
            kidsSidePanel.classList.add('active');
            panelOverlay.classList.add('active');
        });
        kidsMenuItem.addEventListener('mouseleave', function(e) {
            setTimeout(() => {
                if (!kidsSidePanel.matches(':hover')) {
                    kidsSidePanel.classList.remove('active');
                    panelOverlay.classList.remove('active');
                }
            }, 100);
        });
        kidsSidePanel.addEventListener('mouseleave', function() {
            kidsSidePanel.classList.remove('active');
            panelOverlay.classList.remove('active');
        });
        if(closeKidsPanelBtn) {
            closeKidsPanelBtn.addEventListener('click', function() {
                closeAllPanels();
            });
        }
    }

    if(panelOverlay) {
        panelOverlay.addEventListener('click', function() {
            closeAllPanels();
        });
    }
});
