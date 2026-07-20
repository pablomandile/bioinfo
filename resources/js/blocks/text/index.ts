import type { BlockDefinition } from '@/blocks/types';
import TextBlock from './TextBlock.vue';

const definition: BlockDefinition = {
    type: 'text',
    label: 'Texto',
    renderer: TextBlock,
};

export default definition;
