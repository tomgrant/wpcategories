#Project notes
    - While developing I made sure to store the id of the taxonomy as meta so that I would avoid any potential conflicts if the target wp site was using these ID's I kept in mind making sure the plugin should have minimal impact on any exisitng data in a set up. 
    - I opt in for an ajax request on the setting page for 'update categories now', I in general prefer to work in this way
    - Most of the work was done within the termsmeta table, I made use of SQL when dealing with this table


#Work left in progress
    - Removing the option for a user to add a category. As far as I'm aware there isn't an option for me to unhook for this one. Some options I considered was removing the capability for all non admin users ($role->remove_cap('manage_categories')), other less effective methods would be to js and CSS remove the options (These options would obviosuly be full of possible exploits and so weren't used).
    - I'm not confident that the Cron scheduleing is perfect, ideally I would use a service such as setcronjob to call the endpoint on a half hourly schedule, but for a plug and play plugin, this wouldn't be a great out of the box solution

#What I thought of the Test
    Having worked with every aspect of this test at some point before in my previous work I didn't find it overly challanging, but it did get me thinking of interesting ways to solve the problem. 

#How long the Test Took
    I spent about 2.5 hours on the development of the plugin itself and about and hour on environment setup

#Demo
    A live demo can be found at https://tomgrant.me/projects/wpcategories/wp-admin
    Login 
        UserName LiveDemo
        Password SDFK&32(Lhh
