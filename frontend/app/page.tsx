export default function Home() {
  return (
    <main className="min-h-screen bg-slate-950 text-white">
      <section className="mx-auto flex min-h-screen max-w-7xl flex-col justify-center px-6 py-24 sm:px-10 lg:px-16">
        <div className="max-w-3xl">
          <div className="mb-8 inline-flex items-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-slate-300">
            AEGIS · Academic Project Management
          </div>

          <h1 className="text-5xl font-semibold tracking-tight sm:text-6xl lg:text-7xl">
            Manage student projects with clarity and confidence.
          </h1>

          <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-300 sm:text-xl">
            AEGIS is a student project management system designed to connect
            students, supervisors, coordinators, and administrators in one
            structured academic workspace.
          </p>

          <div className="mt-10 flex flex-col gap-4 sm:flex-row">
            <button
              type="button"
              className="rounded-xl bg-white px-6 py-3.5 font-medium text-slate-950 transition hover:bg-slate-200"
            >
              Sign in to AEGIS
            </button>

            <button
              type="button"
              className="rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 font-medium text-white transition hover:bg-white/10"
            >
              Explore the platform
            </button>
          </div>
        </div>

        <div className="mt-20 grid gap-4 sm:grid-cols-3">
          {[
            {
              title: "Students",
              description: "Create, manage, and submit academic projects.",
            },
            {
              title: "Supervisors",
              description: "Guide projects through review and supervision.",
            },
            {
              title: "Coordinators",
              description: "Coordinate projects across academic programs.",
            },
          ].map((item) => (
            <div
              key={item.title}
              className="rounded-2xl border border-white/10 bg-white/5 p-6"
            >
              <h2 className="text-lg font-semibold">{item.title}</h2>
              <p className="mt-2 text-sm leading-6 text-slate-400">
                {item.description}
              </p>
            </div>
          ))}
        </div>
      </section>
    </main>
  );
}