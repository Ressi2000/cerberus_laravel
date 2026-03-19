# Componentes en revisión

Estos componentes fueron movidos aquí durante la reorganización de la Fase 1 (Cerberus 2.0).
Están sin usar en el sistema actual pero se conservan por si hay referencias no detectadas.

**Revisar y borrar manualmente cuando confirmes que no se usan.**

---

## danger-button.blade.php
**Reemplazado por:** clases Tailwind directas `bg-red-600 hover:bg-red-700 text-white` 
o usar `x-auth.secondary-button` con clases de color rojo.  
No se encontraron usos activos en las vistas.

## dropdown.blade.php + dropdown-link.blade.php
**Reemplazado por:** Alpine.js `x-data="{ open: false }"` directo en cada componente  
que lo necesita (navbar, table-actions). El patrón inline es más claro y no requiere  
un wrapper genérico.

## nav-link.blade.php + responsive-nav-link.blade.php
**Reemplazado por:** enlaces directos en `ui/sidebar.blade.php` con clases Tailwind  
y detección de ruta activa via `request()->routeIs()`.  
Eran parte del layout de Laravel Breeze original, ya no aplica.

## content.blade.php
**Era:** un wrapper vacío con `<main>` y un `<div>` de Breeze.  
**Reemplazado por:** el layout `layouts/app.blade.php` que ya maneja el contenido.

## view-modal.blade.php (raíz)
**Reemplazado por:** `modal/delete-modal.blade.php` + modales Livewire específicos  
(`livewire/admin/usuario-view-modal.blade.php`, `livewire/equipos/equipo-view-modal.blade.php`).  
El modal genérico de raíz nunca fue flexible suficiente para los datos reales.

---

Para borrar todo: `rm -rf resources/views/components/_legacy/`
