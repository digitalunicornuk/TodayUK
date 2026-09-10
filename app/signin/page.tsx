import SignIn from './signin';
export const dynamic='force-dynamic';
export default function Page(){return <SignIn url={process.env.SUPABASE_URL??''} publishableKey={process.env.SUPABASE_PUBLISHABLE_KEY??''}/>;}
