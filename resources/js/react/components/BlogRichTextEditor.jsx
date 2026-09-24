import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Underline from '@tiptap/extension-underline';
import { TableKit } from '@tiptap/extension-table';
import Placeholder from '@tiptap/extension-placeholder';
import {
  Bold,
  Italic,
  Underline as UnderlineIcon,
  List,
  ListOrdered,
  Link2,
  Unlink,
  Image as ImageIcon,
  Quote,
  Code,
  Heading1,
  Heading2,
  Heading3,
  Table as TableIcon,
  Undo2,
  Redo2,
  FileUp,
} from 'lucide-react';
import { isBlogContentEmpty } from '../utils/blogContent';
import { importMarkdownFile } from '../utils/markdownImport';
import './blogRichTextEditor.css';

const extensions = [
  StarterKit.configure({
    heading: { levels: [1, 2, 3, 4] },
    horizontalRule: false,
    strike: false,
  }),
  Underline,
  Link.configure({
    openOnClick: false,
    HTMLAttributes: {
      rel: 'noopener noreferrer',
      target: '_blank',
    },
  }),
  Image.configure({
    inline: false,
    allowBase64: false,
  }),
  TableKit.configure({ table: { resizable: false } }),
];

function ToolbarButton({ onClick, active, disabled, title, children }) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      title={title}
      className={`blog-rich-text-editor__btn${active ? ' is-active' : ''}`}
    >
      {children}
    </button>
  );
}

function EditorToolbar({ editor, onImportMarkdown, importMarkdownTitle }) {
  if (!editor) return null;

  const setLink = () => {
    const previous = editor.getAttributes('link').href;
    const url = window.prompt('Link URL (https://…)', previous || 'https://');
    if (url === null) return;
    if (url === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
      return;
    }
    editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
  };

  const addImage = () => {
    const url = window.prompt('Image URL (https://…)');
    if (url) {
      editor.chain().focus().setImage({ src: url }).run();
    }
  };

  const insertTable = () => {
    editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
  };

  return (
    <div className="blog-rich-text-editor__toolbar" role="toolbar" aria-label="Formatting">
      <div className="blog-rich-text-editor__toolbar-group">
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
          active={editor.isActive('heading', { level: 1 })}
          title="Heading 1"
        >
          <Heading1 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
          active={editor.isActive('heading', { level: 2 })}
          title="Heading 2"
        >
          <Heading2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
          active={editor.isActive('heading', { level: 3 })}
          title="Heading 3"
        >
          <Heading3 className="h-4 w-4" />
        </ToolbarButton>
      </div>

      <div className="blog-rich-text-editor__toolbar-group">
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleBold().run()}
          active={editor.isActive('bold')}
          title="Bold"
        >
          <Bold className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleItalic().run()}
          active={editor.isActive('italic')}
          title="Italic"
        >
          <Italic className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleUnderline().run()}
          active={editor.isActive('underline')}
          title="Underline"
        >
          <UnderlineIcon className="h-4 w-4" />
        </ToolbarButton>
      </div>

      <div className="blog-rich-text-editor__toolbar-group">
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          active={editor.isActive('bulletList')}
          title="Bullet list"
        >
          <List className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          active={editor.isActive('orderedList')}
          title="Numbered list"
        >
          <ListOrdered className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleBlockquote().run()}
          active={editor.isActive('blockquote')}
          title="Quote"
        >
          <Quote className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().toggleCodeBlock().run()}
          active={editor.isActive('codeBlock')}
          title="Code block"
        >
          <Code className="h-4 w-4" />
        </ToolbarButton>
      </div>

      <div className="blog-rich-text-editor__toolbar-group">
        <ToolbarButton onClick={setLink} active={editor.isActive('link')} title="Add link">
          <Link2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().unsetLink().run()}
          disabled={!editor.isActive('link')}
          title="Remove link"
        >
          <Unlink className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton onClick={addImage} title="Insert image">
          <ImageIcon className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton onClick={insertTable} title="Insert table">
          <TableIcon className="h-4 w-4" />
        </ToolbarButton>
      </div>

      <div className="blog-rich-text-editor__toolbar-group">
        <ToolbarButton
          onClick={() => editor.chain().focus().undo().run()}
          disabled={!editor.can().undo()}
          title="Undo"
        >
          <Undo2 className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          onClick={() => editor.chain().focus().redo().run()}
          disabled={!editor.can().redo()}
          title="Redo"
        >
          <Redo2 className="h-4 w-4" />
        </ToolbarButton>
      </div>

      {onImportMarkdown ? (
        <div className="blog-rich-text-editor__toolbar-group blog-rich-text-editor__toolbar-group--end">
          <ToolbarButton onClick={onImportMarkdown} title={importMarkdownTitle}>
            <FileUp className="h-4 w-4" />
          </ToolbarButton>
        </div>
      ) : null}
    </div>
  );
}

export default function BlogRichTextEditor({
  value,
  onChange,
  placeholder = 'Write your blog post…',
  hasError = false,
  editorKey = 'default',
  onImportMetadata,
  labels = {},
}) {
  const fileInputRef = useRef(null);

  const editor = useEditor(
    {
      extensions: [
        ...extensions,
        Placeholder.configure({ placeholder }),
      ],
      content: value || '',
      editorProps: {
        attributes: {
          class: 'tiptap',
        },
      },
      onUpdate: ({ editor: ed }) => {
        onChange(ed.getHTML());
      },
    },
    [editorKey]
  );

  useEffect(() => {
    if (!editor) return;
    const current = editor.getHTML();
    const next = value || '';
    if (next !== current) {
      editor.commands.setContent(next, false);
    }
  }, [value, editor]);

  const handleImportClick = () => {
    fileInputRef.current?.click();
  };

  const handleMarkdownFileChange = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file || !editor) return;

    try {
      const result = await importMarkdownFile(file, {
        existingContentEmpty: isBlogContentEmpty(value),
        confirmReplaceMessage: labels.importReplaceConfirm,
      });
      if (result.cancelled) return;

      onChange(result.html);
      if (onImportMetadata && Object.keys(result.fields || {}).length > 0) {
        onImportMetadata(result.fields);
      }

      const successMsg =
        typeof labels.importSuccess === 'string'
          ? labels.importSuccess.replace('{name}', result.fileName)
          : `Imported ${result.fileName}`;
      toast.success(successMsg);
    } catch (err) {
      toast.error(err.message || labels.importError || 'Failed to import Markdown file');
    }
  };

  return (
    <div
      className={`blog-rich-text-editor${hasError ? ' blog-rich-text-editor--error' : ''}`}
    >
      <input
        ref={fileInputRef}
        type="file"
        accept=".md,.markdown,text/markdown,text/plain"
        className="sr-only"
        tabIndex={-1}
        aria-hidden
        onChange={handleMarkdownFileChange}
      />
      <EditorToolbar
        editor={editor}
        onImportMarkdown={handleImportClick}
        importMarkdownTitle={labels.importMarkdown || 'Import Markdown (.md)'}
      />
      <div className="blog-rich-text-editor__content">
        <EditorContent editor={editor} />
      </div>
      {labels.importHint ? (
        <p className="blog-rich-text-editor__import-hint">{labels.importHint}</p>
      ) : null}
    </div>
  );
}
