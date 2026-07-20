import type { BlockDefinition } from '@/blocks/types';
import { Youtube as YoutubeIcon } from 'lucide-vue-next';
import EmbedBlock from './EmbedBlock.vue';
import EmbedEditor from './EmbedEditor.vue';

const definition: BlockDefinition = {
    type: 'embed',
    label: 'Embed',
    category: 'media',
    icon: YoutubeIcon,
    renderer: EmbedBlock,
    editor: EmbedEditor,
    defaultData: () => ({ provider: 'youtube', id: '' }),
};

export default definition;
