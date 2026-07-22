<script>
function openIndicatorModal(tipo) {
    document.getElementById('modalTipoIndicador').value = tipo;
    document.getElementById('indicatorModal').style.display = 'flex';
}
function closeIndicatorModal() {
    document.getElementById('indicatorModal').style.display = 'none';
}
function openNormaModal() {
    document.getElementById('normaModal').style.display = 'flex';
}
function closeNormaModal() {
    document.getElementById('normaModal').style.display = 'none';
}
window.onclick = function(event) {
    let modalNorma = document.getElementById('normaModal');
    let modalInd = document.getElementById('indicatorModal');
    if (event.target === modalNorma) modalNorma.style.display = 'none';
    if (event.target === modalInd) modalInd.style.display = 'none';
}

// Función estándar para controlar los acordeones de la vista
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.ri-arrow-down-s-line, i[class*="ri-arrow"]');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        if (icon) {
            icon.style.transform = 'rotate(180deg)';
            icon.style.transition = 'transform 0.2s ease';
        }
    } else {
        content.style.display = 'none';
        if (icon) {
            icon.style.transform = 'rotate(0deg)';
        }
    }
}

function openIndicatorModal(tipo) {
    document.getElementById('modalTipoIndicador').value = tipo;
    document.getElementById('indicatorModal').style.display = 'flex';
}

function closeIndicatorModal() {
    document.getElementById('indicatorModal').style.display = 'none';
}

function openNormaModal() {
    document.getElementById('normaModal').style.display = 'flex';
}

function closeNormaModal() {
    document.getElementById('normaModal').style.display = 'none';
}

window.onclick = function(event) {
    let modalNorma = document.getElementById('normaModal');
    let modalInd = document.getElementById('indicatorModal');
    if (event.target === modalNorma) modalNorma.style.display = 'none';
    if (event.target === modalInd) modalInd.style.display = 'none';
}
</script>


<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>