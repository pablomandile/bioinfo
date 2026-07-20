import type { BlockDefinition } from '@/blocks/types';
import { Heading as HeadingIcon } from 'lucide-vue-next';
import HeadingBlock from './HeadingBlock.vue';
import HeadingEditor from './HeadingEditor.vue';

const definition: BlockDefinition = {
    type: 'heading',
    label: 'Encabezado',
    category: 'basic',
    icon: HeadingIcon,
    renderer: HeadingBlock,
    editor: HeadingEditor,
    defaultData: () => ({ text: 'Encabezado' }),
};

export default definition;
