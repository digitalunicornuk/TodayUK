export type Engagement={mode:'custom'|'off';tools:string[];question:string;options:string[];area:string;topic:string;org:string;event_date:string};
export const engagementLabels:Record<string,string>={poll:'Poll',nimby:'Planning rating',attendance:'Event attendance',seen:'Seen this',helpful:'Helpful'};
export function suggestEngagement(text:string):Engagement{
 const tool=/planning|development|housing proposal/i.test(text)?'nimby':/festival|concert|harvest|event/i.test(text)?'attendance':/missing|lost pet|lost cat|lost dog/i.test(text)?'seen':'helpful';
 return {mode:'custom',tools:[tool,'share'],question:'How important is this issue to you?',options:['Very important','Somewhat important','Not important','Not sure'],area:'',topic:'',org:'',event_date:''};
}
export function validateEngagement(value:unknown):Engagement|null{
 if(value===null||value===undefined)return null;
 if(typeof value!=='object')throw Error('Choose a reader engagement tool.');
 const c=value as Engagement;
 if(!['custom','off'].includes(c.mode)||!Array.isArray(c.tools)||c.tools.length>2||c.tools.some(t=>!Object.hasOwn(engagementLabels,t)&&t!=='share'))throw Error('Invalid engagement choice.');
 if(typeof c.question!=='string'||c.question.length>180||!Array.isArray(c.options)||c.options.length>6||c.options.some(o=>typeof o!=='string'||o.length>100))throw Error('Check the poll question and answers.');
 if(c.mode==='custom'&&(!c.tools.some(t=>t!=='share')||c.tools.includes('poll')&&(!c.question.trim()||c.options.length<2||c.options.some(o=>!o.trim())||new Set(c.options).size!==c.options.length)))throw Error('A poll needs a question and two to six different answers.');
 return {mode:c.mode,tools:c.mode==='off'?[]:[...new Set(c.tools)],question:c.question.trim(),options:c.options.map(o=>o.trim()),area:'',topic:'',org:'',event_date:''};
}
