import type { BlockDefinition } from '@/blocks/types';

/**
 * Registry de bloques con auto-registro. Para añadir un tipo nuevo basta con
 * crear resources/js/blocks/<tipo>/index.ts que exporte por defecto una
 * BlockDefinition; no hay que tocar ningún switch.
 */
const modules = import.meta.glob<{ default: BlockDefinition }>('../../blocks/*/index.ts', { eager: true });

export const blockRegistry: Record<string, BlockDefinition> = {};

for (const mod of Object.values(modules)) {
    const def = mod.default;
    if (def?.type) {
        blockRegistry[def.type] = def;
    }
}

export function resolveBlock(type: string): BlockDefinition | undefined {
    return blockRegistry[type];
}
