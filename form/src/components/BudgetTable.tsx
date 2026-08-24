// src/components/BudgetTable.tsx

import type { UseFormRegister, FieldErrors, UseFormWatch, Control, FieldValues, Path, ArrayPath } from 'react-hook-form'
import { useFieldArray } from 'react-hook-form'

// 助成金要望額の上限（円）
const GRANT_REQUEST_MAX = 500000

const INCOME_FIELDS = [
  { label: '助成金要望額',     field: 'income.grantRequest', memoField: 'income.incomeMemo.grantRequest' },
  { label: '会費・会員から',   field: 'income.memberFees',   memoField: 'income.incomeMemo.memberFees'   },
  { label: '寄付金・他助成金', field: 'income.donations',    memoField: 'income.incomeMemo.donations'    },
  { label: 'チケット・入場料', field: 'income.tickets',      memoField: 'income.incomeMemo.tickets'      },
] as const

type Props<T extends FieldValues> = {
  register: UseFormRegister<T>
  errors: FieldErrors<T>
  watch: UseFormWatch<T>
  control: Control<T>
  // フィールド名のプレフィックス（例: 'section3' / 'reportSection2'）
  prefix: string
  // 収入の部の説明文
  incomeDescription?: string
  // 支出の部の説明文
  expenseDescription?: string
  // 削除可能な最小インデックス（申請: 1 / 報告: 0）
  removableFrom?: number
}

function BudgetTable<T extends FieldValues>({
  register,
  errors,
  watch,
  control,
  prefix,
  incomeDescription,
  expenseDescription,
  removableFrom = 0,
}: Props<T>) {

  const { fields, append, remove } = useFieldArray({
    control,
    name: `${prefix}.expenses` as ArrayPath<T>,
  })

  // 収入合計
  const incomeTotal =
    (Number(watch(`${prefix}.income.grantRequest` as Path<T>)) || 0) +
    (Number(watch(`${prefix}.income.memberFees`   as Path<T>)) || 0) +
    (Number(watch(`${prefix}.income.donations`    as Path<T>)) || 0) +
    (Number(watch(`${prefix}.income.tickets`      as Path<T>)) || 0)

  // 支出合計・助成金使用額合計
  const expenses     = (watch(`${prefix}.expenses` as Path<T>) as any[]) ?? []
  const expenseTotal    = expenses.reduce((sum, r) => sum + (Number(r.amount)     || 0), 0)
  const grantUsageTotal = expenses.reduce((sum, r) => sum + (Number(r.grantUsage) || 0), 0)

  // 検算
  const grantRequest = Number(watch(`${prefix}.income.grantRequest` as Path<T>)) || 0

  return (
    <>
      {/* 収入の部 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">収入の部</h3>
          {incomeDescription && (
            <p className="mt-1 text-sm text-slate-500">{incomeDescription}</p>
          )}
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-165 w-full border-collapse">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="p-4 text-left text-sm font-bold text-slate-700 w-60">科目</th>
                <th className="p-4 text-left text-sm font-bold text-slate-700 w-60">金額</th>
                <th className="p-4 text-left text-sm font-bold text-slate-700">摘要</th>
              </tr>
            </thead>
            <tbody>
              {INCOME_FIELDS.map(({ label, field, memoField }) => {
                const fullField     = `${prefix}.${field}`
                const fullMemoField = `${prefix}.${memoField}`
                const incomeErrors  = (errors as any)?.[prefix]?.income
                return (
                  <tr key={field} className="border-b border-slate-100">
                    <td className="p-4 align-top">
                      <div className="font-bold text-slate-700 py-3">{label}</div>
                    </td>
                    <td className="p-4 align-top">
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={0}
                          max={field === 'income.grantRequest' ? GRANT_REQUEST_MAX : undefined}
                          className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                          {...register(fullField as Path<T>, {
                            min: { value: 0, message: '0以上を入力してください' },
                            ...(field === 'income.grantRequest'
                              ? {
                                  max: {
                                    value: GRANT_REQUEST_MAX,
                                    message: `助成金要望額は${GRANT_REQUEST_MAX.toLocaleString()}円以下で入力してください`,
                                  },
                                }
                              : {}),
                            validate: (v: any, formValues: any) => {
                              if (field !== 'income.grantRequest') return true
                              if (!v || Number(v) <= 0) return '助成金要望額を入力してください'

                              // 助成金要望額は、支出の部で「この事業に助成金をいくら
                              // 充てるか」を積み上げた金額（助成金使用額合計）と
                              // 一致している必要がある。支出行が未入力（＝合計0）の
                              // 場合も不一致として検出する（合計が0より大きい場合しか
                              // 判定していなかったのが従来のバグ）。
                              const currentExpenses =
                                ((formValues as any)?.[prefix]?.expenses ?? []) as any[]
                              const currentGrantUsageTotal = currentExpenses.reduce(
                                (sum, r) => sum + (Number(r?.grantUsage) || 0),
                                0,
                              )
                              return Number(v) === currentGrantUsageTotal
                                ? true
                                : `助成金要望額と支出の部の助成金使用額合計（${currentGrantUsageTotal.toLocaleString()}円）が一致していません`
                            },
                          })}
                        />
                        <span className="text-slate-500 whitespace-nowrap">円</span>
                      </div>
                      {field === 'income.grantRequest' && incomeErrors?.grantRequest && (
                        <p className="text-red-500 text-sm mt-1">
                          {incomeErrors.grantRequest.message}
                        </p>
                      )}
                    </td>
                    <td className="p-4 align-top">
                      <input
                        type="text"
                        className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                        {...register(fullMemoField as Path<T>)}
                      />
                    </td>
                  </tr>
                )
              })}
            </tbody>
            <tfoot className="bg-sky-50 border-t border-sky-100">
              <tr>
                <td className="p-4 font-bold text-sky-900">合計</td>
                <td className="p-4">
                  <div className="flex items-center gap-2">
                    <input
                      type="number"
                      readOnly
                      value={incomeTotal}
                      className="w-full p-3 border border-slate-200 rounded-lg bg-sky-50 text-sky-800 font-bold"
                    />
                    <span className="text-slate-500 whitespace-nowrap">円</span>
                  </div>
                </td>
                <td className="p-4 text-sm text-slate-400">自動計算</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      {/* 支出の部 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100 flex items-center justify-between">
          <div>
            <h3 className="font-bold text-sky-900 text-lg">支出の部</h3>
            {expenseDescription && (
              <p className="mt-1 text-sm text-slate-500">{expenseDescription}</p>
            )}
          </div>
          <button
            type="button"
            onClick={() => append({ id: String(Date.now()), subject: '', amount: 0, grantUsage: 0, memo: '' } as any)}
            className="px-4 py-2 rounded-lg bg-sky-500 text-white text-sm font-bold hover:bg-sky-600 transition"
          >
            行を追加
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="min-w-240 w-full border-collapse">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="p-4 text-left text-sm font-bold text-slate-700 w-60">科目</th>
                <th className="p-4 text-left text-sm font-bold text-slate-700 w-45">支出額</th>
                <th className="p-4 text-left text-sm font-bold text-slate-700 w-45">助成金使用額</th>
                <th className="p-4 text-left text-sm font-bold text-slate-700">摘要</th>
                <th className="p-4 w-12" />
              </tr>
            </thead>
            <tbody>
              {/* 例示行 */}
              <tr className="border-b border-orange-100 bg-orange-50/60">
                <td className="p-4 align-top">
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-bold text-orange-400 border border-orange-300 rounded px-1.5 py-0.5 whitespace-nowrap">例</span>
                    <input type="text" readOnly value="会場費" className="w-full p-3 border border-orange-200 rounded-lg bg-transparent text-orange-400 cursor-default" tabIndex={-1} />
                  </div>
                </td>
                <td className="p-4 align-top">
                  <div className="flex items-center gap-2">
                    <input type="text" readOnly value="100,000" className="w-full p-3 border border-orange-200 rounded-lg bg-transparent text-orange-400 cursor-default" tabIndex={-1} />
                    <span className="text-orange-300 whitespace-nowrap">円</span>
                  </div>
                </td>
                <td className="p-4 align-top">
                  <div className="flex items-center gap-2">
                    <input type="text" readOnly value="100,000" className="w-full p-3 border border-orange-200 rounded-lg bg-transparent text-orange-400 cursor-default" tabIndex={-1} />
                    <span className="text-orange-300 whitespace-nowrap">円</span>
                  </div>
                </td>
                <td className="p-4 align-top">
                  <input type="text" readOnly value="〇〇センター 使用料" className="w-full p-3 border border-orange-200 rounded-lg bg-transparent text-orange-400 cursor-default" tabIndex={-1} />
                </td>
                <td />
              </tr>

              {fields.map((field, index) => {
                const expenseErrors = (errors as any)?.[prefix]?.expenses?.[index]
                return (
                  <tr key={field.id} className="border-b border-slate-100">
                    <td className="p-4 align-top">
                      <input
                        type="text"
                        className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                        {...register(`${prefix}.expenses.${index}.subject` as Path<T>, {
                          required: '科目を入力してください',
                        })}
                      />
                      {expenseErrors?.subject && (
                        <p className="text-red-500 text-sm mt-1">{expenseErrors.subject.message}</p>
                      )}
                    </td>
                    <td className="p-4 align-top">
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={0}
                          className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                          {...register(`${prefix}.expenses.${index}.amount` as Path<T>, {
                            min: { value: 0, message: '0以上を入力してください' },
                          })}
                        />
                        <span className="text-slate-500 whitespace-nowrap">円</span>
                      </div>
                    </td>
                    <td className="p-4 align-top">
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={0}
                          className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                          {...register(`${prefix}.expenses.${index}.grantUsage` as Path<T>, {
                            min: { value: 0, message: '0以上を入力してください' },
                            validate: (v: any, formValues: any) => {
                              const rowAmount = Number(formValues[prefix]?.expenses[index]?.amount) || 0
                              return Number(v) <= rowAmount || '助成金使用額は支出額を超えられません'
                            },
                          })}
                        />
                        <span className="text-slate-500 whitespace-nowrap">円</span>
                      </div>
                      {expenseErrors?.grantUsage && (
                        <p className="text-red-500 text-sm mt-1">{expenseErrors.grantUsage.message}</p>
                      )}
                    </td>
                    <td className="p-4 align-top">
                      <input
                        type="text"
                        className="w-full p-3 border border-slate-300 rounded-lg bg-white"
                        {...register(`${prefix}.expenses.${index}.memo` as Path<T>)}
                      />
                    </td>
                    <td className="p-4 align-top text-center">
                      {index >= removableFrom && (
                        <button
                          type="button"
                          onClick={() => remove(index)}
                          className="text-slate-400 hover:text-red-500 transition text-lg leading-none"
                          aria-label="行を削除"
                        >
                          ×
                        </button>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
            <tfoot className="bg-sky-50 border-t border-sky-100">
              <tr>
                <td className="p-4 font-bold text-sky-900">合計</td>
                <td className="p-4">
                  <div className="flex items-center gap-2">
                    <input type="number" readOnly value={expenseTotal} className="w-full p-3 border border-slate-200 rounded-lg bg-sky-50 text-sky-800 font-bold" />
                    <span className="text-slate-500 whitespace-nowrap">円</span>
                  </div>
                </td>
                <td className="p-4">
                  <div className="flex items-center gap-2">
                    <input type="number" readOnly value={grantUsageTotal} className="w-full p-3 border border-slate-200 rounded-lg bg-sky-50 text-sky-800 font-bold" />
                    <span className="text-slate-500 whitespace-nowrap">円</span>
                  </div>
                </td>
                <td className="p-4 text-sm text-slate-400">自動計算</td>
                <td />
              </tr>
            </tfoot>
          </table>
        </div>

        {/* 検算 */}
        {grantRequest > 0 && grantRequest !== grantUsageTotal && (() => {
          const diff = grantUsageTotal - grantRequest
          return (
            <div className="mx-5 mb-5 p-4 rounded-xl border border-orange-200 bg-orange-50 text-sm text-orange-700">
              助成金要望額（{grantRequest.toLocaleString()}円）と助成金使用額合計（{grantUsageTotal.toLocaleString()}円）が一致していません。<br />
              {diff > 0
                ? `（使用額が ${diff.toLocaleString()} 円多い状態です）`
                : `（使用額が ${Math.abs(diff).toLocaleString()} 円少ない状態です）`
              }
            </div>
          )
        })()}
      </section>

      {/* 備考 */}
      <section className="rounded-2xl border border-sky-100 bg-white overflow-hidden shadow-sm">
        <div className="px-6 py-4 bg-sky-50 border-b border-sky-100">
          <h3 className="font-bold text-sky-900 text-lg">備考</h3>
        </div>
        <div className="p-5">
          <textarea
            className="w-full min-h-45 p-4 border border-slate-300 rounded-xl bg-white"
            {...register(`${prefix}.budgetNote` as Path<T>)}
          />
        </div>
      </section>
    </>
  )
}

export default BudgetTable
