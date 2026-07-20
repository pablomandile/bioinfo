import type { BlockDefinition } from '@/blocks/types';
import HeadingBlock from './HeadingBlock.vue';

const definition: BlockDefinition = {
    type: 'heading',
    label: 'Encabezado',
    renderer: HeadingBlock,
};

export default definition;
