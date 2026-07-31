// src/components/ReceiptUploader.tsx

import type { Control, FieldValues, Path } from 'react-hook-form'
import { Controller } from 'react-hook-form'

type Props<T extends FieldValues> = {
  control: Control<T>
  name: Path<T>
}

function ReceiptUploader<T extends FieldValues>({ control, name }: Props<T>) {
  return (
    <>
      <Controller
        name={name}
        control={control}
        defaultValue={[] as any}
        rules={{
          validate: (files: File[]) => {
            if (!files || files.length === 0) return '領収書をアップロードしてください'
            for (const file of files) {
              if (file.size > 10 * 1024 * 1024) {
                return `${file.name} のファイルサイズが10MBを超えています`
              }
            }
            return true
          },
        }}
        render={({ field }) => {
          const files = field.value ?? []

          const handleAdd = (e: React.ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0]
            if (file) field.onChange([...files, file])
            e.target.value = ''
          }

          const handleRemove = (index: number) => {
            const updated = [...files]
            updated.splice(index, 1)
            field.onChange(updated)
          }

          return (
            <div className="space-y-3">

              {/* 選択済みファイル一覧 */}
              {files.map((file: File, index: number) => (
                <div
                  key={index}
                  className="flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-orange-200 bg-orange-50/60"
                >
                  <div className="flex items-center gap-2 text-sm text-orange-700 min-w-0">
                    <span className="text-orange-400 shrink-0">✓</span>
                    <span className="truncate">{file.name}</span>
                    <span className="text-slate-400 shrink-0">
                      ({(file.size / 1024).toFixed(0)} KB)
                    </span>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleRemove(index)}
                    className="text-slate-400 hover:text-red-500 transition text-lg leading-none shrink-0"
                    aria-label="削除"
                  >
                    ×
                  </button>
                </div>
              ))}

              {/* 追加ボタン */}
              <div className="rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/30 p-5">
                <label className="flex items-center gap-3 cursor-pointer">
                  <span className="px-4 py-2 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold transition whitespace-nowrap">
                    ファイルを追加
                  </span>
                  <span className="text-sm text-slate-500">
                    {files.length === 0 ? 'ファイルを選択してください' : `現在 ${files.length} ファイル`}
                  </span>
                  <input
                    type="file"
                    accept="application/pdf,image/jpeg,image/png"
                    className="hidden"
                    onChange={handleAdd}
                  />
                </label>
              </div>

            </div>
          )
        }}
      />
    </>
  )
}

export default ReceiptUploader
