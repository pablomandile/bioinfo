import type { BlockDefinition } from '@/blocks/types';
import { Type as TypeIcon } from 'lucide-vue-next';
import TextBlock from './TextBlock.vue';
import TextEditor from './TextEditor.vue';

const definition: BlockDefinition = {
    type: 'text',
    label: 'Texto',
    category: 'basic',
    icon: TypeIcon,
    renderer: TextBlock,
    editor: TextEditor,
    defaultData: () => ({ text: 'Escribe algo…' }),
};

export default definition;
