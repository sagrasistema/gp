<?php // v/proyectos/js-proyectos.php ?>
<script>
function toggleAccordion(headerElement) {
    const item = headerElement.parentElement;
    const content = item.querySelector('.accordion-content');
    
    if (item.classList.contains('active')) {
        item.classList.remove('active');
        content.style.display = 'none';
    } else {
        // Cerrar los demás acordeones abiertos para mantener limpia la pantalla
        document.querySelectorAll('.accordion-item').forEach(el => {
            el.classList.remove('active');
            const c = el.querySelector('.accordion-content');
            if(c) c.style.display = 'none';
        });
        
        item.classList.add('active');
        content.style.display = 'block';
    }
}
</script>