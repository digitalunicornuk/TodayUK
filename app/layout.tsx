import type { Metadata } from 'next';
import './globals.css';
export const metadata: Metadata = {title:'TodayUK · CR Newsroom',description:'TodayUK Publishing OS. The CR News platform foundation.'};
export default function RootLayout({children}:Readonly<{children:React.ReactNode}>){return <html lang="en-GB"><body>{children}</body></html>}
