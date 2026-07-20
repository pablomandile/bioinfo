import type { BlockDefinition } from '@/blocks/types';
import EmbedBlock from './EmbedBlock.vue';

const definition: BlockDefinition = {
    type: 'embed',
    label: 'Embed',
    renderer: EmbedBlock,
};

export default definition;
