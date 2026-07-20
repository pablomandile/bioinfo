import type { BlockDefinition } from '@/blocks/types';
import { Image as ImageIcon } from 'lucide-vue-next';
import ImageBlock from './ImageBlock.vue';
import ImageEditor from './ImageEditor.vue';

const definition: BlockDefinition = {
    type: 'image',
    label: 'Imagen',
    category: 'media',
    icon: ImageIcon,
    renderer: ImageBlock,
    editor: ImageEditor,
    defaultData: () => ({ url: '', alt: '' }),
};

export default definition;
