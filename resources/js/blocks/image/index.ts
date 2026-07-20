import type { BlockDefinition } from '@/blocks/types';
import ImageBlock from './ImageBlock.vue';

const definition: BlockDefinition = {
    type: 'image',
    label: 'Imagen',
    renderer: ImageBlock,
};

export default definition;
