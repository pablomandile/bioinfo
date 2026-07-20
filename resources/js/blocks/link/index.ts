import type { BlockDefinition } from '@/blocks/types';
import LinkBlock from './LinkBlock.vue';

const definition: BlockDefinition = {
    type: 'link',
    label: 'Enlace',
    renderer: LinkBlock,
};

export default definition;
