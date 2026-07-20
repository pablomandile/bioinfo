import type { Component } from 'vue';

/**
 * Contrato de un tipo de bloque. El registry (lib/blocks/registry.ts) los
 * auto-carga desde resources/js/blocks/<tipo>/index.ts.
 *
 * En la Fase 1.2 se añade `editor` (formulario del panel) y `defaultData`.
 */
export interface BlockDefinition {
    type: string;
    label: string;
    /** Componente que renderiza el bloque en la página pública. */
    renderer: Component;
    /** Layouts en los que aparece (por defecto: ambos). */
    layouts?: Array<'list' | 'grid'>;
}
