import type { Component } from 'vue';

/**
 * Contrato de un tipo de bloque. El registry (lib/blocks/registry.ts) los
 * auto-carga desde resources/js/blocks/<tipo>/index.ts.
 *
 * Añadir un tipo nuevo = crear la carpeta con index.ts + los componentes,
 * y su caso en App\Blocks\BlockTypeRegistry (backend).
 */
export interface BlockDefinition {
    type: string;
    label: string;
    /** Ícono (lucide-vue-next) para el menú "Añadir bloque". */
    icon?: Component;
    category?: 'basic' | 'media' | 'social';
    /** Render público del bloque. */
    renderer: Component;
    /** Formulario de edición del bloque en el panel. */
    editor?: Component;
    /** Datos iniciales al crear (espejo cliente de BlockTypeRegistry). */
    defaultData?: () => Record<string, unknown>;
    /** Layouts soportados (por defecto: ambos). */
    layouts?: Array<'list' | 'grid'>;
}
