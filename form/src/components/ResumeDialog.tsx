// src/components/ResumeDialog.tsx

type Props = {
  onResume: () => void
  onStartOver: () => void
  note?: string
}

function ResumeDialog({ onResume, onStartOver, note }: Props) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/40" />
      <div className="relative bg-white rounded-2xl shadow-xl p-8 mx-4 max-w-md w-full space-y-5">
        <div>
          <p className="text-lg font-bold text-slate-800">入力途中のデータがあります</p>
          <p className="mt-2 text-sm text-slate-500 leading-6">
            前回の入力内容を引き継いで再開しますか？<br />
            「最初から入力する」を選ぶと保存データは削除されます。
            {note && (
              <><br /><span className="text-orange-500">{note}</span></>
            )}
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            onClick={onResume}
            className="flex-1 py-3 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-bold transition"
          >
            再開する
          </button>
          <button
            type="button"
            onClick={onStartOver}
            className="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition"
          >
            最初から入力する
          </button>
        </div>
      </div>
    </div>
  )
}

export default ResumeDialog
