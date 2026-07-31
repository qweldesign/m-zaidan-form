// src/pages/Application/Section3.tsx

import type { Control } from 'react-hook-form'
import type { FormData } from '../../types/form'
import BudgetTable from '../../components/BudgetTable'

type Props = {
  control: Control<FormData>
}

function Section3({ control }: Props) {
  return (
    <section className="space-y-8">

      {/* ページタイトル */}
      <div>
        <p className="text-sm text-sky-600 font-bold mb-2">STEP 3 / 5</p>
        <h2 className="text-3xl font-bold text-slate-800">Ⅲ．収支予算書</h2>
      </div>

      {/* 説明 */}
      <div className="rounded-2xl border border-orange-100 bg-orange-50 p-5">
        <p className="text-sm leading-7 text-slate-700">
          申請事業の収支予算をご記入ください。<br />
          支出の部の「助成金使用額」には、本助成金を充当予定の金額をご入力ください。<br />
          助成金は下記の使途には使用できません（「助成金使用額」に計上しない）。<br />
          1. 申請事業と関係のない日常的な活動・運営経費<br />
          2. 事業・活動における飲食代（食事給付サービス等は除く。）
        </p>
      </div>

      <BudgetTable
        control={control}
        prefix="section3"
        incomeDescription="申請事業に関する収入予定をご記入ください。"
        expenseDescription="申請事業に関する支出予定をご記入ください。必要に応じて行を追加してください。"
        removableFrom={1}
      />

    </section>
  )
}

export default Section3
