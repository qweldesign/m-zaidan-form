// src/components/ReceiptUploader.tsx

import type { Control, FieldErrors, FieldValues, Path } from 'react-hook-form'
import { Controller } from 'react-hook-form'

type Props<T extends FieldValues> = {
  control: Control<T>
  errors: FieldErrors<T>
  name: Path<T>
  isEditMode?: boolean
  maxFiles?: number
}

// maxFiles のデフォルト値について：
// 領収書には枚数上限が無く、post_max_size（リクエスト全体のサイズ上限）を
// 超えるほど大量にアップロードされてしまう可能性があった。完了報告フォームは
// 活動実施写真（最大2枚×5MB＝10MB）と合わせて1リクエストになるため、
// 領収書1ファイルあたりの上限（5MB）を踏まえ、10枚（最大50MB）を上限とする。
// 写真分と合わせても60MB程度となり、`.user.ini` の post_max_size（80M）に
// 十分収まる。
const DEFAULT_MAX_FILES = 10

function ReceiptUploader<T extends FieldValues>({ control, errors, name, isEditMode = false, maxFiles = DEFAULT_MAX_FILES }: Props<T>) {
  return (
    <>
      <Controller
        name={name}
        control={control}
        defaultValue={[] as any}
        rules={{
          validate: (files: File[]) => {
            if (!isEditMode && (!files || files.length === 0)) return '領収書をアップロードしてください'
            if (files && files.length > maxFiles) return `領収書は最大${maxFiles}枚までです`
            for (const file of files ?? []) {
              if (file.size > 5 * 1024 * 1024) return `${file.name} のファイルサイズが5MBを超えています`
            }
            return true
          },
        }}
        render={({ field }) => {
          const files    = field.value ?? []
          const isAtMax  = files.length >= maxFiles

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

              {/* 追加ボタン（上限に達したら非表示にし、代わりに上限メッセージを出す） */}
              {isAtMax ? (
                <p className="text-sm text-slate-500">
                  領収書は最大{maxFiles}枚まで添付できます（現在 {files.length} ファイル）
                </p>
              ) : (
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
              )}

            </div>
          )
        }}
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

export default ReceiptUploader
