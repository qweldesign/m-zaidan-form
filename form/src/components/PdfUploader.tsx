// src/components/PdfUploader.tsx

import type { Control, FieldValues, Path, FieldErrors } from 'react-hook-form'
import { Controller } from 'react-hook-form'

type Props<T extends FieldValues> = {
  control: Control<T>
  errors: FieldErrors<T>
  name: Path<T>
  label: string
  isEditMode?: boolean
}

// 単一必須PDFファイルのアップロード欄。
// Controller経由でreact-hook-formの中央stateにFileオブジェクトを保持することで、
// このコンポーネントがアンマウント・再マウントされても選択済みファイルが
// 失われないようにしている（STEPの切り替えで <input type="file"> 自体を
// register() だけで非制御に扱っていた旧実装では、STEPを離れると
// ブラウザ側のDOM要素ごと選択状態が消えてしまっていた）。
function PdfUploader<T extends FieldValues>({ control, errors, name, label, isEditMode = false }: Props<T>) {
  return (
    <>
      <Controller
        name={name}
        control={control}
        defaultValue={null as any}
        rules={{
          validate: (file: File | null) => {
            if (!isEditMode && !file) return `${label}をアップロードしてください`
            if (file && file.size > 10 * 1024 * 1024) return `ファイルサイズが10MBを超えています`
            return true
          },
        }}
        render={({ field }) => (
          <div className="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <label className="inline-flex items-center gap-3 cursor-pointer">
              <span className="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold transition whitespace-nowrap">
                {field.value ? 'ファイルを変更' : 'ファイルを選択'}
              </span>
              <input
                type="file"
                accept="application/pdf"
                className="hidden"
                onChange={(e) => {
                  const file = e.target.files?.[0] ?? null
                  field.onChange(file)
                  e.target.value = ''
                }}
              />
            </label>

            {field.value ? (
              <p className="mt-3 text-sm text-orange-700 flex items-center gap-2">
                <span className="text-orange-400">✓</span>
                <span className="truncate max-w-64">{field.value.name}</span>
                <span className="text-slate-400">
                  ({(field.value.size / 1024).toFixed(0)} KB)
                </span>
                <button
                  type="button"
                  onClick={() => field.onChange(null)}
                  className="text-slate-400 hover:text-red-500 transition text-lg leading-none ml-1"
                  aria-label="削除"
                >×</button>
              </p>
            ) : (
              <p className="mt-3 text-sm text-slate-500">ファイルが選択されていません</p>
            )}
          </div>
        )}
      />
      {(() => {
        const key = name.split('.').reduce((obj: any, k) => obj?.[k], errors)
        return key?.message
          ? <p className="text-red-500 text-sm mt-2">{key.message as string}</p>
          : null
      })()}
    </>
  )
}

export default PdfUploader
