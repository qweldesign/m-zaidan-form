// src/components/PhotoSlots.tsx

import type { Control, FieldValues, Path, FieldErrors } from 'react-hook-form'
import { Controller } from 'react-hook-form'

type Props<T extends FieldValues> = {
  control: Control<T>
  errors: FieldErrors<T>
  name: Path<T>
  maxSlots: number
  required?: boolean
  isEditMode?: boolean
}

function PhotoSlots<T extends FieldValues>({ control, errors, name, maxSlots, isEditMode = false }: Props<T>) {
  return (
    <>
      <Controller
        name={name}
        control={control}
        defaultValue={[] as any}
        // PhotoSlots の rules を変更
        rules={{
          validate: (files: File[]) => {
            if (!isEditMode && (!files || files.length === 0)) return '写真を1枚以上アップロードしてください'
            if (files && files.length > 3) return '写真は最大3枚までです'
            for (const file of files ?? []) {
              if (file.size > 5 * 1024 * 1024) return `${file.name} のファイルサイズが5MBを超えています`
            }
            return true
          },
        }}
        render={({ field }) => (
          <div className="space-y-3">
            {Array.from({ length: maxSlots }, (_, index) => (
              <div key={index} className="rounded-2xl border-2 border-dashed border-sky-200 bg-sky-50/50 p-5">
                <div className="flex items-center gap-3">
                  <span className="text-xs font-bold text-sky-400 whitespace-nowrap">{index + 1} 枚目</span>
                  <input
                    type="file"
                    accept="image/jpeg,image/png"
                    className="block w-full text-sm text-slate-600
                      file:mr-4 file:px-4 file:py-2 file:rounded-lg file:border-0
                      file:bg-sky-500 file:text-white file:font-bold hover:file:bg-sky-600"
                    onChange={(e) => {
                      const file    = e.target.files?.[0]
                      const current = [...(field.value ?? [])] as File[]
                      if (file) {
                        current[index] = file
                      } else {
                        current.splice(index, 1)
                      }
                      field.onChange(current.filter(Boolean))
                    }}
                  />
                  {field.value?.[index] && (
                    <div className="flex items-center gap-2 text-sm text-sky-700 whitespace-nowrap">
                      <span className="text-sky-400">✓</span>
                      <span className="truncate max-w-40">{field.value[index].name}</span>
                      <span className="text-slate-400">({(field.value[index].size / 1024).toFixed(0)} KB)</span>
                      <button
                        type="button"
                        onClick={() => {
                          const current = [...(field.value ?? [])]
                          current.splice(index, 1)
                          field.onChange(current)
                        }}
                        className="text-slate-400 hover:text-red-500 transition text-lg leading-none ml-1"
                        aria-label="削除"
                      >×</button>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      />
      {(errors as any)[name.split('.')[0]]?.photos && (
        <p className="text-red-500 text-sm mt-2">
          {(errors as any)[name.split('.')[0]].photos.message}
        </p>
      )}
    </>
  )
}

export default PhotoSlots
